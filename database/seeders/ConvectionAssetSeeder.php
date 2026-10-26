<?php

namespace Database\Seeders;

use App\Models\ConvectionAsset;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConvectionAssetSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        $assets = [
            [
                'code' => 'AST-001',
                'name' => 'Mesin Jahit High-Speed Juki DDL-8100e',
                'category' => ConvectionAsset::CATEGORY_MACHINERY,
                'purchase_date' => '2024-03-15',
                'quantity' => 6,
                'unit' => 'unit',
                'purchase_price' => 24000000, // 4jt x 6
                'current_value' => 19500000,
                'condition' => ConvectionAsset::CONDITION_GOOD,
                'location' => 'Lantai 1 - Jalur Jahit Utama',
                'notes' => 'Mesin jahit jarum 1 servo motor hemat listrik untuk kemeja, kaos, dan polo.',
            ],
            [
                'code' => 'AST-002',
                'name' => 'Mesin Obras Benang 4 Pegasus M800',
                'category' => ConvectionAsset::CATEGORY_MACHINERY,
                'purchase_date' => '2024-05-10',
                'quantity' => 3,
                'unit' => 'unit',
                'purchase_price' => 18000000, // 6jt x 3
                'current_value' => 15000000,
                'condition' => ConvectionAsset::CONDITION_GOOD,
                'location' => 'Lantai 1 - Jalur Obras',
                'notes' => 'Obras benang 4 kecepatan tinggi untuk kaos oblong, polo, dan jaket.',
            ],
            [
                'code' => 'AST-003',
                'name' => 'Mesin Overdeck / Kansai Special FX-4404',
                'category' => ConvectionAsset::CATEGORY_MACHINERY,
                'purchase_date' => '2024-08-20',
                'quantity' => 2,
                'unit' => 'unit',
                'purchase_price' => 17000000, // 8.5jt x 2
                'current_value' => 14500000,
                'condition' => ConvectionAsset::CONDITION_GOOD,
                'location' => 'Lantai 1 - Jalur Finishing Hemming',
                'notes' => 'Kansai jarum 3 untuk kelim bawah dan lengan kaos serta polo.',
            ],
            [
                'code' => 'AST-004',
                'name' => 'Mesin Potong Kain Tegak 8 Inch KM Straight Knife',
                'category' => ConvectionAsset::CATEGORY_CUTTING_FINISHING,
                'purchase_date' => '2024-02-18',
                'quantity' => 2,
                'unit' => 'unit',
                'purchase_price' => 7000000,
                'current_value' => 5800000,
                'condition' => ConvectionAsset::CONDITION_GOOD,
                'location' => 'Ruang Pola & Potong',
                'notes' => 'Kapasitas tumpukan kain potong hingga 16 cm.',
            ],
            [
                'code' => 'AST-005',
                'name' => 'Meja Potong Kain Kayu Jati Belanda Besi Hollow (7m x 2.2m)',
                'category' => ConvectionAsset::CATEGORY_CUTTING_FINISHING,
                'purchase_date' => '2024-01-25',
                'quantity' => 1,
                'unit' => 'set',
                'purchase_price' => 8500000,
                'current_value' => 7500000,
                'condition' => ConvectionAsset::CONDITION_GOOD,
                'location' => 'Ruang Pola & Potong',
                'notes' => 'Meja potong presisi dengan rel penjepit kain dan penggaris akrilik.',
            ],
            [
                'code' => 'AST-006',
                'name' => 'Meja Sablon Presisi Rel Panjang 12 Palet & Flash Curing',
                'category' => ConvectionAsset::CATEGORY_PRINTING_EMBROIDERY,
                'purchase_date' => '2024-04-12',
                'quantity' => 1,
                'unit' => 'set',
                'purchase_price' => 16500000,
                'current_value' => 14000000,
                'condition' => ConvectionAsset::CONDITION_GOOD,
                'location' => 'Workshop Sablon Belakang',
                'notes' => 'Dilengkapi pemanas flash curing inframerah dan screen aluminium.',
            ],
            [
                'code' => 'AST-007',
                'name' => 'Mesin Heat Press Digital Pneumatic High-Pressure 40x60 cm',
                'category' => ConvectionAsset::CATEGORY_PRINTING_EMBROIDERY,
                'purchase_date' => '2024-06-08',
                'quantity' => 1,
                'unit' => 'unit',
                'purchase_price' => 5500000,
                'current_value' => 4500000,
                'condition' => ConvectionAsset::CONDITION_GOOD,
                'location' => 'Ruang Sablon & DTF',
                'notes' => 'Untuk press transfer film DTF, polyflex, dan curing sablon plastisol.',
            ],
            [
                'code' => 'AST-008',
                'name' => 'Setrika Uap Boiler Maqi Gas Otomatis 25 Liter (2 Kepala)',
                'category' => ConvectionAsset::CATEGORY_FINISHING_STEAMER,
                'purchase_date' => '2024-03-30',
                'quantity' => 1,
                'unit' => 'set',
                'purchase_price' => 6200000,
                'current_value' => 5200000,
                'condition' => ConvectionAsset::CONDITION_GOOD,
                'location' => 'Ruang Finishing & QC',
                'notes' => 'Kapasitas uap 25L untuk 2 setrika bersamaan, licin cepat tanpa hangus.',
            ],
            [
                'code' => 'AST-009',
                'name' => 'PC Desain Grafis & Pola Corel/Optitex + Dual Monitor IPS',
                'category' => ConvectionAsset::CATEGORY_OFFICE_TECH,
                'purchase_date' => '2024-02-10',
                'quantity' => 2,
                'unit' => 'unit',
                'purchase_price' => 22000000,
                'current_value' => 17000000,
                'condition' => ConvectionAsset::CONDITION_GOOD,
                'location' => 'Ruang Kantor & Desain',
                'notes' => 'PC workstation Intel Core i7, 32GB RAM, GPU Nvidia RTX untuk mockup dan pola.',
            ],
            [
                'code' => 'AST-010',
                'name' => 'Kendaraan Pick-up Operasional Gran Max 1.3 Box',
                'category' => ConvectionAsset::CATEGORY_FACILITY_VEHICLE,
                'purchase_date' => '2024-07-05',
                'quantity' => 1,
                'unit' => 'unit',
                'purchase_price' => 78000000,
                'current_value' => 68000000,
                'condition' => ConvectionAsset::CONDITION_GOOD,
                'location' => 'Garasi Workshop',
                'notes' => 'Armada angkut kain bahan baku dan pengiriman pesanan partai besar.',
            ],
        ];

        foreach ($assets as $asset) {
            ConvectionAsset::updateOrCreate(
                ['code' => $asset['code']],
                array_merge($asset, ['recorded_by' => $admin?->id])
            );
        }
    }
}
