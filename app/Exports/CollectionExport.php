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
    protected $columns;
    protected $filters;
    protected $sortBy;
    protected $sortOrder;

    // Mapping des colonnes
    protected $columnMapping = [
        'vinyl_nom' => 'Nom du vinyle',
        'artiste' => 'Artiste',
        'vinyl_titre' => 'Titre',
        'vinyl_format' => 'Format',
        'label' => 'Label',
        'reference' => 'Référence',
        'annee' => 'Année',
        'pays' => 'Pays',
        'prix_achat' => 'Prix d\'achat',
        'annee_achat' => 'Année d\'achat',
        'provenance' => 'Provenance',
        'note' => 'Note',
        'commentaires' => 'Commentaires',
        'date_ajout' => 'Date d\'ajout',
        'discogs_id' => 'Discogs ID'
    ];

    public function __construct(Collection $collection, array $columns = [], array $filters = [], string $sortBy = 'date_ajout', string $sortOrder = 'desc')
    {
        $this->collection = $collection;
        $this->columns = empty($columns) ? array_keys($this->columnMapping) : $columns;
        $this->filters = $filters;
        $this->sortBy = $sortBy;
        $this->sortOrder = $sortOrder;
    }

    public function collection()
    {
        $query = $this->collection->collectionVinyls()->with('vinyl');

        // Apply filters
        if (!empty($this->filters['date_ajout_from'])) {
            $query->where('date_ajout', '>=', $this->filters['date_ajout_from']);
        }

        if (!empty($this->filters['date_ajout_to'])) {
            $query->where('date_ajout', '<=', $this->filters['date_ajout_to']);
        }

        if (!empty($this->filters['annee_achat_min'])) {
            $query->where('annee_achat', '>=', $this->filters['annee_achat_min']);
        }

        if (!empty($this->filters['annee_achat_max'])) {
            $query->where('annee_achat', '<=', $this->filters['annee_achat_max']);
        }

        if (!empty($this->filters['prix_min'])) {
            $query->where('prix_achat', '>=', $this->filters['prix_min']);
        }

        if (!empty($this->filters['prix_max'])) {
            $query->where('prix_achat', '<=', $this->filters['prix_max']);
        }

        if (!empty($this->filters['note_min'])) {
            $query->where('note', '>=', $this->filters['note_min']);
        }

        if (!empty($this->filters['provenance'])) {
            $query->where('provenance', 'ILIKE', '%' . $this->filters['provenance'] . '%');
        }

        // Filtres sur les champs du vinyle
        if (!empty($this->filters['annee_sortie_min']) || !empty($this->filters['annee_sortie_max'])) {
            $query->whereHas('vinyl', function($q) {
                if (!empty($this->filters['annee_sortie_min'])) {
                    $q->where('annee', '>=', $this->filters['annee_sortie_min']);
                }
                if (!empty($this->filters['annee_sortie_max'])) {
                    $q->where('annee', '<=', $this->filters['annee_sortie_max']);
                }
            });
        }

        // Apply sorting
        switch ($this->sortBy) {
            // Tris sur les champs de vinyls
            case 'artiste':
            case 'vinyl_nom':
            case 'vinyl_titre':
            case 'vinyl_format':
            case 'label':
            case 'reference':
            case 'annee':
            case 'pays':
            case 'discogs_id':
                $query->orderBy(
                    \DB::table('vinyls')
                        ->select($this->sortBy)
                        ->whereColumn('vinyls.id', 'collection_vinyls.vinyl_id')
                        ->limit(1),
                    $this->sortOrder
                );
                break;

            // Tris sur les champs de collection_vinyls
            case 'prix_achat':
            case 'annee_achat':
            case 'provenance':
            case 'note':
            case 'updated_at':
                $query->orderBy('collection_vinyls.' . $this->sortBy, $this->sortOrder);
                break;

            case 'date_ajout':
            default:
                $query->orderBy('collection_vinyls.date_ajout', $this->sortOrder);
                break;
        }

        return $query->get();
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->columns as $column) {
            $headings[] = $this->columnMapping[$column] ?? $column;
        }
        return $headings;
    }

    public function map($collectionVinyl): array
    {
        $vinyl = $collectionVinyl->vinyl;
        $row = [];

        foreach ($this->columns as $column) {
            switch ($column) {
                case 'vinyl_nom':
                    $row[] = $vinyl->vinyl_nom ?? '';
                    break;
                case 'artiste':
                    $row[] = $vinyl->artiste ?? '';
                    break;
                case 'vinyl_titre':
                    $row[] = $vinyl->vinyl_titre ?? '';
                    break;
                case 'vinyl_format':
                    $row[] = $vinyl->vinyl_format ?? '';
                    break;
                case 'label':
                    $row[] = $vinyl->label ?? '';
                    break;
                case 'reference':
                    $row[] = $vinyl->reference ?? '';
                    break;
                case 'annee':
                    $row[] = $vinyl->annee ?? '';
                    break;
                case 'pays':
                    $row[] = $vinyl->pays ?? '';
                    break;
                case 'prix_achat':
                    $row[] = $collectionVinyl->prix_achat ?? '';
                    break;
                case 'annee_achat':
                    $row[] = $collectionVinyl->annee_achat ?? '';
                    break;
                case 'provenance':
                    $row[] = $collectionVinyl->provenance ?? '';
                    break;
                case 'note':
                    $row[] = $collectionVinyl->note ?? '';
                    break;
                case 'commentaires':
                    $row[] = $collectionVinyl->commentaires ?? '';
                    break;
                case 'date_ajout':
                    $row[] = $collectionVinyl->date_ajout ? \Carbon\Carbon::parse($collectionVinyl->date_ajout)->format('d/m/Y') : '';
                    break;
                case 'discogs_id':
                    $row[] = $vinyl->discogs_id ?? '';
                    break;
                default:
                    $row[] = '';
            }
        }

        return $row;
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

                // Formats spécifiques pour les colonnes dynamiques
                // Trouver l'index des colonnes spécifiques
                $columnLetters = range('A', $highestColumn);

                foreach ($this->columns as $index => $column) {
                    $columnLetter = $columnLetters[$index];

                    // Format monétaire pour le prix
                    if ($column === 'prix_achat') {
                        $sheet->getStyle("{$columnLetter}{$firstDataRow}:{$columnLetter}{$lastDataRow}")
                            ->getNumberFormat()->setFormatCode('#,##0.00 €');
                    }

                    // Colonnes à centrer
                    if (in_array($column, ['annee', 'annee_achat', 'note', 'vinyl_format'])) {
                        $sheet->getStyle("{$columnLetter}{$firstDataRow}:{$columnLetter}{$lastDataRow}")
                            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                }

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