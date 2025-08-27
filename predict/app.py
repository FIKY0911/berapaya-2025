from fastapi import FastAPI, HTTPException, Depends, Security
from fastapi.middleware.cors import CORSMiddleware
from fastapi.security.api_key import APIKeyHeader, APIKey
from pydantic import BaseModel, Field
from typing import List, Literal, Optional

import pandas as pd
import geopandas as gpd
from shapely.geometry import Point
from geopy.distance import geodesic
from sklearn.preprocessing import LabelEncoder
from sklearn.ensemble import RandomForestRegressor

# =========================
# Konfigurasi & Path Data
# =========================
EXCEL_PATH = "estimasi_biaya.xlsx"
GEOJSON_PATH = "rumah_sakit.geojson"

API_KEY = "berapaya"  # ganti sesuai kebutuhan
API_KEY_NAME = "X-API-Key"
api_key_header = APIKeyHeader(name=API_KEY_NAME, auto_error=False)

app = FastAPI(title="Berapa Ya",
              description="Predict for Berapa Ya",
              version="1.1.0")

# CORS (opsional)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# =========================
# Security Dependency
# =========================
async def get_api_key(api_key_header: str = Security(api_key_header)):
    if api_key_header == API_KEY:
        return api_key_header
    else:
        raise HTTPException(status_code=403, detail="Could not validate API KEY")

# =========================
# Utilitas
# =========================
def compute_distance_km(latlon_a, latlon_b) -> float:
    return geodesic(latlon_a, latlon_b).km


def filter_only_hospitals(gdf: gpd.GeoDataFrame) -> gpd.GeoDataFrame:
    gdf2 = gdf.copy()
    cols = [c for c in gdf2.columns]
    mask = pd.Series([False] * len(gdf2))
    if "NAMOBJ" in cols:
        mask = mask | gdf2["NAMOBJ"].astype(str).str.contains("Rumah Sakit", case=False, na=False)
    if "REMARK" in cols:
        mask = mask | gdf2["REMARK"].astype(str).str.contains("Rumah Sakit", case=False, na=False)
    if "TIPSHT" in cols:
        mask = mask | gdf2["TIPSHT"].astype(str).str.contains("Rumah Sakit", case=False, na=False)
    filtered = gdf2[mask]
    return filtered if not filtered.empty else gdf2


# =========================
# Model & Data Global (di-load saat startup)
# =========================
DF: Optional[pd.DataFrame] = None
GDF_HOSP: Optional[gpd.GeoDataFrame] = None
MODEL: Optional[RandomForestRegressor] = None
LABEL_ENCODERS: Optional[dict] = None


class PredictRequest(BaseModel):
    penyakit: str = Field(..., description="Nama penyakit persis seperti di Excel")
    budget: int = Field(5_000_000, description="Budget pengguna (Rupiah)")
    lat: float = Field(-6.2, description="Latitude pengguna (default Jakarta)")
    lon: float = Field(106.8, description="Longitude pengguna (default Jakarta)")
    radius_km: float = Field(10, ge=1, description="Radius pencarian RS dalam km")
    geom_method: Literal["Centroid", "Representative Point"] = Field(
        "Representative Point", description="Metode titik perwakilan geometri"
    )


class HospitalOut(BaseModel):
    name: str
    lat: float
    lon: float
    distance_km: float
    google_maps_directions: str


class PredictResponse(BaseModel):
    penyakit: str
    predicted_cost: float
    budget: int
    budget_ok: bool
    radius_km: float
    hospitals_in_radius: List[HospitalOut]
    count_in_radius: int
    nearest_hospital: HospitalOut
    note: Optional[str] = None


@app.on_event("startup")
def on_startup():
    global DF, GDF_HOSP, MODEL, LABEL_ENCODERS

    # Load Excel
    df = pd.read_excel(EXCEL_PATH)
    df.columns = df.columns.str.strip()

    required_cols = [
        "Kategori", "Penyakit", "Tindakan Medis Utama",
        "Estimasi Min (Rp)", "Estimasi Max (Rp)"
    ]
    missing = [c for c in required_cols if c not in df.columns]
    if missing:
        raise RuntimeError(f"Kolom tidak lengkap di Excel: {missing}. Kolom ada: {list(df.columns)}")

    # Train model (label encoding)
    df_enc = df.copy()
    label_encoders = {}
    for col in ["Kategori", "Penyakit", "Tindakan Medis Utama"]:
        le = LabelEncoder()
        df_enc[col] = le.fit_transform(df_enc[col].astype(str))
        label_encoders[col] = le

    X = df_enc[[
        "Kategori", "Penyakit", "Tindakan Medis Utama",
        "Estimasi Min (Rp)", "Estimasi Max (Rp)"
    ]]
    y = (df_enc["Estimasi Min (Rp)"] + df_enc["Estimasi Max (Rp)"]) / 2.0

    model = RandomForestRegressor(n_estimators=250, random_state=42, n_jobs=-1)
    model.fit(X, y)

    # Load GeoJSON & siapkan CRS
    gdf = gpd.read_file(GEOJSON_PATH)
    try:
        if gdf.crs is None:
            gdf.set_crs(epsg=4326, inplace=True)
        else:
            gdf = gdf.to_crs(epsg=4326)
    except Exception:
        pass

    if "NAMOBJ" not in gdf.columns:
        raise RuntimeError(f"GeoJSON wajib punya kolom 'NAMOBJ'. Kolom tersedia: {list(gdf.columns)}")

    gdf_hosp = filter_only_hospitals(gdf)

    # Precompute titik centroid & representative point untuk efisiensi
    gdf_hosp = gdf_hosp.copy()
    gdf_hosp["centroid_point"] = gdf_hosp.geometry.centroid
    gdf_hosp["repr_point"] = gdf_hosp.geometry.representative_point()
    gdf_hosp["centroid_lat"] = gdf_hosp["centroid_point"].y
    gdf_hosp["centroid_lon"] = gdf_hosp["centroid_point"].x
    gdf_hosp["repr_lat"] = gdf_hosp["repr_point"].y
    gdf_hosp["repr_lon"] = gdf_hosp["repr_point"].x

    # Simpan ke global
    DF = df
    GDF_HOSP = gdf_hosp
    MODEL = model
    LABEL_ENCODERS = label_encoders


@app.get("/health")
def health(api_key: APIKey = Depends(get_api_key)):
    return {"status": "ok"}


@app.get("/metadata")
def metadata(api_key: APIKey = Depends(get_api_key)):
    assert DF is not None
    return {
        "penyakit_list": sorted(list(map(str, DF["Penyakit"].unique()))),
        "kategori_list": sorted(list(map(str, DF["Kategori"].unique()))),
        "tindakan_list": sorted(list(map(str, DF["Tindakan Medis Utama"].unique()))),
        "example_request": {
            "penyakit": str(DF["Penyakit"].iloc[0]),
            "budget": 5000000,
            "lat": -6.2,
            "lon": 106.8,
            "radius_km": 10,
            "geom_method": "Representative Point"
        }
    }


@app.post("/predict-nearby", response_model=PredictResponse)
def predict_nearby(req: PredictRequest, api_key: APIKey = Depends(get_api_key)):
    if DF is None or GDF_HOSP is None or MODEL is None or LABEL_ENCODERS is None:
        raise HTTPException(status_code=503, detail="Model/data belum siap")

    # Validasi penyakit ada di data
    df_match = DF[DF["Penyakit"].astype(str) == req.penyakit]
    if df_match.empty:
        raise HTTPException(status_code=400, detail=f"Penyakit '{req.penyakit}' tidak ditemukan di Excel")

    row_sel = df_match.iloc[0]

    # Siapkan fitur prediksi
    X_pred = pd.DataFrame([{
        "Kategori": LABEL_ENCODERS["Kategori"].transform([row_sel["Kategori"]])[0],
        "Penyakit": LABEL_ENCODERS["Penyakit"].transform([row_sel["Penyakit"]])[0],
        "Tindakan Medis Utama": LABEL_ENCODERS["Tindakan Medis Utama"].transform([row_sel["Tindakan Medis Utama"]])[0],
        "Estimasi Min (Rp)": float(row_sel["Estimasi Min (Rp)"]),
        "Estimasi Max (Rp)": float(row_sel["Estimasi Max (Rp)"])
    }])

    predicted_cost = float(MODEL.predict(X_pred)[0])
    budget_ok = bool(req.budget >= predicted_cost)

    # Pilih titik geometri
    if req.geom_method == "Centroid":
        lat_col, lon_col = "centroid_lat", "centroid_lon"
    else:
        lat_col, lon_col = "repr_lat", "repr_lon"

    # Hitung jarak
    gdf_tmp = GDF_HOSP[["NAMOBJ", lat_col, lon_col]].copy()
    gdf_tmp.rename(columns={lat_col: "lat", lon_col: "lon"}, inplace=True)

    # Compute distances
    gdf_tmp["distance_km"] = gdf_tmp.apply(
        lambda r: compute_distance_km((req.lat, req.lon), (r["lat"], r["lon"])), axis=1
    )

    # Filter radius
    nearby = gdf_tmp[gdf_tmp["distance_km"] <= req.radius_km].sort_values("distance_km")
    note = None
    if nearby.empty:
        note = f"Tidak ada RS dalam radius {req.radius_km} km. Mengembalikan RS terdekat secara global."
        nearby = gdf_tmp.sort_values("distance_km").head(30)

    # RS terdekat (global atau dalam radius)
    nearest_row = nearby.iloc[0]

    def map_hosp_row(r: pd.Series) -> HospitalOut:
        url = f"https://www.google.com/maps/dir/{req.lat},{req.lon}/{float(r['lat'])},{float(r['lon'])}"
        return HospitalOut(
            name=str(r["NAMOBJ"]),
            lat=float(r["lat"]),
            lon=float(r["lon"]),
            distance_km=float(round(r["distance_km"], 4)),
            google_maps_directions=url,
        )

    hospitals_out = [map_hosp_row(r) for _, r in nearby.iterrows()]
    nearest_out = map_hosp_row(nearest_row)

    return PredictResponse(
        penyakit=req.penyakit,
        predicted_cost=round(predicted_cost, 2),
        budget=req.budget,
        budget_ok=budget_ok,
        radius_km=req.radius_km,
        hospitals_in_radius=hospitals_out,
        count_in_radius=len(hospitals_out) if note is None else 0,
        nearest_hospital=nearest_out,
        note=note,
    )


# =========================
# Cara Menjalankan:
# =========================
# 1) Install dependensi:
#    pip install fastapi uvicorn pandas geopandas geopy scikit-learn shapely pyproj fiona
# 2) Jalankan server:
#    uvicorn app:app --reload --port 8000
# 3) Setiap request HARUS sertakan header:
#    X-API-Key: mysecretapikey
# 4) Buka dokumentasi interaktif di:
#    http://127.0.0.1:8000/docs
