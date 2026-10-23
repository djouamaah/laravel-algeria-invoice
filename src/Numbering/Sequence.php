<?php

namespace Djouamaah\AlgeriaInvoice\Numbering;

use Djouamaah\AlgeriaInvoice\Enums\DocumentType;
use Illuminate\Database\ConnectionInterface;

/**
 * Gap-free invoice numbers: one counter per series and year, incremented under a row lock.
 *
 * Call next() inside the same database transaction that saves your invoice: if saving fails,
 * the transaction rolls back and the number is not consumed, so the sequence keeps no gap.
 */
class Sequence
{
    public function __construct(
        protected ConnectionInterface $db,
        protected array $config = [],
    ) {
    }

    /**
     * Reserve the next number: next() -> "FA-2026-00001", next('credit_note') -> "AV-2026-00001".
     */
    public function next(DocumentType|string $type = DocumentType::Invoice, ?int $year = null, ?string $series = null): string
    {
        $series ??= $this->series($type);
        $year ??= (int) date('Y');

        $number = $this->db->transaction(function () use ($series, $year) {
            $table = $this->db->table($this->table());

            // creates the counter the first time a series is used in a year; a concurrent insert is ignored
            $table->clone()->insertOrIgnore(['series' => $series, 'year' => $year, 'last_number' => 0]);

            $current = (int) $table->clone()
                ->where('series', $series)->where('year', $year)
                ->lockForUpdate()
                ->value('last_number');

            $table->clone()
                ->where('series', $series)->where('year', $year)
                ->update(['last_number' => $current + 1, 'updated_at' => date('Y-m-d H:i:s')]);

            return $current + 1;
        });

        return $this->format($series, $year, $number);
    }

    /**
     * The last number used, without reserving a new one (0 when the series has not started).
     */
    public function current(DocumentType|string $type = DocumentType::Invoice, ?int $year = null, ?string $series = null): int
    {
        return (int) $this->db->table($this->table())
            ->where('series', $series ?? $this->series($type))
            ->where('year', $year ?? (int) date('Y'))
            ->value('last_number');
    }

    public function format(string $series, int $year, int $number): string
    {
        return strtr($this->config['format'] ?? '{series}-{year}-{number}', [
            '{series}' => $series,
            '{year}' => (string) $year,
            '{number}' => str_pad((string) $number, (int) ($this->config['pad'] ?? 5), '0', STR_PAD_LEFT),
        ]);
    }

    protected function series(DocumentType|string $type): string
    {
        $type = is_string($type) ? DocumentType::from($type) : $type;

        return $this->config['series'][$type->value] ?? ['invoice' => 'FA', 'credit_note' => 'AV', 'proforma' => 'PF'][$type->value];
    }

    protected function table(): string
    {
        return $this->config['table'] ?? 'invoice_sequences';
    }
}
