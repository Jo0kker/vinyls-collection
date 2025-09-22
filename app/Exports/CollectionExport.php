<?php

namespace App\Exports;

use App\Models\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CollectionExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    protected $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    public function collection()
    {
        return $this->collection->collectionVinyls()->with('vinyl')->get();
    }

    public function headings(): array
    {
        return [
            'Nom du vinyle',
            'Artiste',
            'Titre',
            'Format',
            'Label',
            'Référence',
            'Année',
            'Pays',
            'Prix d\'achat',
            'Année d\'achat',
            'Provenance',
            'Note',
            'Commentaires',
            'Date d\'ajout',
            'Discogs ID'
        ];
    }

    public function map($collectionVinyl): array
    {
        $vinyl = $collectionVinyl->vinyl;

        return [
            $vinyl->vinyl_nom ?? '',
            $vinyl->artiste ?? '',
            $vinyl->vinyl_titre ?? '',
            $vinyl->vinyl_format ?? '',
            $vinyl->label ?? '',
            $vinyl->reference ?? '',
            $vinyl->annee ?? '',
            $vinyl->pays ?? '',
            $collectionVinyl->prix_achat ?? '',
            $collectionVinyl->annee_achat ?? '',
            $collectionVinyl->provenance ?? '',
            $collectionVinyl->note ?? '',
            $collectionVinyl->commentaires ?? '',
            $collectionVinyl->date_ajout ? \Carbon\Carbon::parse($collectionVinyl->date_ajout)->format('d/m/Y') : '',
            $vinyl->discogs_id ?? ''
        ];
    }

    public function title(): string
    {
        return 'Collection - ' . $this->collection->collection_nom;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style pour l'en-tête
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'], // Violet
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $initialHighestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // PREMIÈRE ÉTAPE : Insérer les lignes du titre et de l'espacement
                // Insérer 2 lignes au début (titre + ligne vide)
                $sheet->insertNewRowBefore(1, 2);

                // Configurer le titre (ligne 1)
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->setCellValue('A1', "Collection : {$this->collection->collection_nom}");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => '1F2937'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E5E7EB'],
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(35);

                // Ligne vide (ligne 2)
                $sheet->getRowDimension(2)->setRowHeight(10);

                // MAINTENANT les en-têtes sont en ligne 3 et les données commencent en ligne 4
                $headerRow = 3;
                $firstDataRow = 4;
                $lastDataRow = $initialHighestRow + 2; // +2 car on a inséré 2 lignes

                // Style pour les en-têtes (ligne 3)
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 12,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4F46E5'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(30);

                // Style pour toutes les cellules de données
                $sheet->getStyle("A{$firstDataRow}:{$highestColumn}{$lastDataRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                // Alternance de couleurs pour les lignes de données
                for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
                    if (($row - $firstDataRow) % 2 == 1) {
                        $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F8FAFC'],
                            ],
                        ]);
                    }
                }

                // Formats spécifiques pour les colonnes
                // Colonne prix (I) en format monétaire
                $sheet->getStyle("I{$firstDataRow}:I{$lastDataRow}")->getNumberFormat()
                    ->setFormatCode('#,##0.00 €');

                // Colonnes centrées
                $sheet->getStyle("G{$firstDataRow}:G{$lastDataRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("J{$firstDataRow}:J{$lastDataRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("L{$firstDataRow}:L{$lastDataRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Ajuster la hauteur des lignes de données
                for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(25);
                }

                // Figer les lignes au-dessus des données (titre + en-têtes)
                $sheet->freezePane('A' . $firstDataRow);

                // Ajouter les filtres automatiques sur les en-têtes
                $sheet->setAutoFilter("A{$headerRow}:{$highestColumn}{$lastDataRow}");

                // Bordure de contour pour tout le tableau
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$lastDataRow}")->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '4F46E5'],
                        ],
                    ],
                ]);
            },
        ];
    }
}