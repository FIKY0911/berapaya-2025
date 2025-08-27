<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiagnosisType: string implements HasLabel
{
    case BATU_EMPDU_RINGAN = 'Batu Empedu Ringan';
    case BATU_GINJAL = 'Batu Ginjal';
    case CAESAR = 'Caesar';
    case CAMPAK = 'Campak';
    case DEMAM_BERDARAH = 'Demam Berdarah';
    case DIARE_BERAT = 'Diare Berat';
    case GAGAL_GINJAL = 'Gagal Ginjal';
    case KANKER_PARU = 'Kanker Paru';
    case KANKER_PAYUDARA = 'Kanker Payudara';
    case OPERASI_BYPASS = 'Operasi Bypass';
    case PATAH_TULANG = 'Patah Tulang';
    case PEMASANGAN_RING = 'Pemasangan Ring';
    case PERSALINAN_NORMAL = 'Persalinan Normal';
    case PNEUMONIA = 'Pneumonia';
    case PNEUMONIA_BERAT_COVID = 'Pneumonia Berat / COVID-19';
    case SERANGAN_JANTUNG = 'Serangan Jantung';
    case SKOLIOSIS = 'Skoliosis';
    case STROKE = 'Stroke';
    case TIFUS = 'Tifus';
    case TUBERKULOSIS = 'Tuberkulosis (TBC)';
    case TUMOR_OTAK = 'Tumor Otak';
    case USUS_BUNTU = 'Usus Buntu';

    public function getLabel(): string
    {
        return $this->value;
    }
}
