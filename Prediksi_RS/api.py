from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import pandas as pd
import geopandas as gpd
from geopy.distance import geodesic
from sklearn.preprocessing import LabelEncoder
from sklearn.ensemble import RandomForestRegressor

# =========================
# Load data & training
# =========================
EXCEL_PATH = "Estimasi Biaya.xlsx"
GEOJSON_PATH = "rumah_sakit.geojson"

df = pd.read_excel(EXCEL_PATH)
df.columns = df.columns.str.strip()

required_cols = ["Kategori", "Penyakit", "Tindakan Medis Utama", "Estimasi Min (Rp)", "Estimasi Max (Rp)"]
missing = [c for c in required_cols if c not in df.columns]
if missing:
    raise RuntimeError(f"Kolom tidak lengkap di Excel: {missing}")

gdf = gpd.read_file(GEOJSON_PATH)
if gdf.crs is None:
    gdf.set_crs(epsg=4326, inplace=True)
else:
    gdf = gdf.to_crs(epsg=4326)

def filter_only_hospitals(gdf):
    gdf2 = gdf.copy()
    cols = gdf2.columns
    mask = pd.Series([False] * len(gdf2))
    if "NAMOBJ" in cols:
        mask |= gdf2["NAMOBJ"].str.contains("Rumah Sakit", case=False, na=False)
    if "REMARK" in cols:
        mask |= gdf2["REMARK"].str.contains("Rumah Sakit", case=False, na=False)
    if "TIPSHT" in cols:
        mask |= gdf2["TIPSHT"].astype(str).str.contains("Rumah Sakit", case=False, na=False)
    return gdf2[mask] if not gdf2[mask].empty else gdf2

gdf = filter_only_hospitals(gdf)

# Train RandomForest
label_encoders = {}
df_enc = df.copy()
for col in ["Kategori", "Penyakit", "Tindakan Medis Utama"]:
    le = LabelEncoder()
    df_enc[col] = le.fit_transform(df_enc[col].astype(str))
    label_encoders[col] = le

X = df_enc[["Kategori", "Penyakit", "Tindakan Medis Utama", "Estimasi Min (Rp)", "Estimasi Max (Rp)"]]
y = (df_enc["Estimasi Min (Rp)"] + df_enc["Estimasi Max (Rp)"]) / 2.0

model = RandomForestRegressor(n_estimators=250, random_state=42, n_jobs=-1)
model.fit(X, y)

# =========================
# API
# =========================
app = FastAPI(title="API Prediksi Estimasi Biaya Rumah Sakit")

class PredictRequest(BaseModel):
    penyakit: str
    lat: float
    lon: float
    radius_km: float

def process_prediction(penyakit: str, lat: float, lon: float, radius_km: float):
    # Filter penyakit
    rows = df[df["Penyakit"] == penyakit]
    if rows.empty:
        raise HTTPException(status_code=404, detail="Penyakit tidak ditemukan di dataset.")
    row_sel = rows.iloc[0]

    # Prediksi biaya
    X_pred = pd.DataFrame([{
        "Kategori": label_encoders["Kategori"].transform([row_sel["Kategori"]])[0],
        "Penyakit": label_encoders["Penyakit"].transform([row_sel["Penyakit"]])[0],
        "Tindakan Medis Utama": label_encoders["Tindakan Medis Utama"].transform([row_sel["Tindakan Medis Utama"]])[0],
        "Estimasi Min (Rp)": row_sel["Estimasi Min (Rp)"],
        "Estimasi Max (Rp)": row_sel["Estimasi Max (Rp)"]
    }])
    predicted_cost = float(model.predict(X_pred)[0])

    # Cari RS terdekat
    points = []
    for idx, r in gdf.iterrows():
        try:
            p = r.geometry.representative_point()
            d = geodesic((lat, lon), (p.y, p.x)).km
            if d <= radius_km:
                points.append({
                    "nama": r["NAMOBJ"],
                    "lat": p.y,
                    "lon": p.x,
                    "distance_km": round(d, 2)
                })
        except Exception:
            continue

    # Fallback jika kosong
    if not points:
        for idx, r in gdf.iterrows():
            try:
                p = r.geometry.representative_point()
                d = geodesic((lat, lon), (p.y, p.x)).km
                points.append({
                    "nama": r["NAMOBJ"],
                    "lat": p.y,
                    "lon": p.x,
                    "distance_km": round(d, 2)
                })
            except Exception:
                continue
        points = sorted(points, key=lambda x: x["distance_km"])[:1]
    else:
        points = sorted(points, key=lambda x: x["distance_km"])

    return {"penyakit": penyakit, "estimasi_biaya": predicted_cost, "rs_terdekat": points}

@app.post("/predict")
def predict_post(req: PredictRequest):
    return process_prediction(req.penyakit, req.lat, req.lon, req.radius_km)

@app.get("/predict")
def predict_get(penyakit: str, lat: float, lon: float, radius_km: float):
    return process_prediction(penyakit, lat, lon, radius_km)
