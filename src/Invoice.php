<?php

namespace Djouamaah\AlgeriaInvoice;

use DateTimeImmutable;
use DateTimeInterface;
use Djouamaah\AlgeriaInvoice\Compliance\Checker;
use Djouamaah\AlgeriaInvoice\Enums\DocumentType;
use Djouamaah\AlgeriaInvoice\Enums\PaymentMethod;
use Djouamaah\AlgeriaInvoice\Tax\StampDuty;

/**
 * An invoice, credit note or proforma. Fluent and framework-free: nothing is stored.
 */
class Invoice
{
    protected DocumentType $type = DocumentType::Invoice;

    protected ?string $number = null;

    protected DateTimeInterface $date;

    protected ?DateTimeInterface $dueDate = null;

    protected ?Party $seller = null;

    protected ?Party $buyer = null;

    /** @var Line[] */
    protected array $lines = [];

    protected ?PaymentMethod $paymentMethod = null;

    /** Number of the invoice a credit note cancels or corrects. */
    protected ?string $originalNumber = null;

    /** Purchase order (bon de commande). */
    protected ?Reference $order = null;

    /** @var Reference[] Delivery notes (bons de livraison), several for a summary invoice. */
    protected array $deliveryNotes = [];

    protected ?string $notes = null;

    protected ?StampDuty $stampDuty = null;

    public function __construct()
    {
        $this->date = new DateTimeImmutable('today');
    }

    public static function make(): static
    {
        return new static;
    }

    public function type(DocumentType|string $type): static
    {
        $this->type = is_string($type) ? DocumentType::from($type) : $type;

        return $this;
    }

    public function number(?string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function date(DateTimeInterface|string $date): static
    {
        $this->date = is_string($date) ? new DateTimeImmutable($date) : $date;

        return $this;
    }

    public function dueDate(DateTimeInterface|string|null $date): static
    {
        $this->dueDate = is_string($date) ? new DateTimeImmutable($date) : $date;

        return $this;
    }

    public function seller(Party|array $seller): static
    {
        $this->seller = is_array($seller) ? Party::make($seller) : $seller;

        return $this;
    }

    public function buyer(Party|array $buyer): static
    {
        $this->buyer = is_array($buyer) ? Party::make($buyer) : $buyer;

        return $this;
    }

    /**
     * ->addLine('Ordinateur', 2, 85000)  or  ->addLine(new Line(...))  or  ->addLine(['description' => ...])
     */
    public function addLine(Line|array|string $line, float $quantity = 1, int|float|string $unitPrice = 0, int|float $vatRate = 19, ?string $unit = null, float $discount = 0.0): static
    {
        $this->lines[] = match (true) {
            $line instanceof Line => $line,
            is_array($line) => Line::make($line),
            default => new Line($line, $quantity, $unitPrice, $vatRate, $unit, $discount),
        };

        return $this;
    }

    /**
     * @param  array<int, Line|array>  $lines
     */
    public function lines(array $lines): static
    {
        $this->lines = [];
        foreach ($lines as $line) {
            $this->addLine($line);
        }

        return $this;
    }

    public function payment(PaymentMethod|string|null $method): static
    {
        $this->paymentMethod = is_string($method) ? PaymentMethod::from($method) : $method;

        return $this;
    }

    public function original(?string $invoiceNumber): static
    {
        $this->originalNumber = $invoiceNumber;

        return $this;
    }

    /**
     * The purchase order (bon de commande): ->order('BC-2026-118', '2026-10-20'). Null removes it.
     */
    public function order(Reference|array|string|null $number, DateTimeInterface|string|null $date = null): static
    {
        $this->order = $number === null || $number === '' ? null : Reference::make($number, $date);

        return $this;
    }

    /**
     * Same as order() without a date; kept for compatibility with 1.0.
     */
    public function orderReference(?string $reference): static
    {
        return $this->order($reference);
    }

    /**
     * Add a delivery note (bon de livraison): ->deliveryNote('BL-2026-0451', '2026-10-21').
     * Call it once per delivery note for an invoice that groups several deliveries.
     */
    public function deliveryNote(Reference|array|string $number, DateTimeInterface|string|null $date = null): static
    {
        $note = Reference::make($number, $date);
        $this->deliveryNotes = array_values(array_filter($this->deliveryNotes, fn (Reference $r) => $r->number !== $note->number));
        $this->deliveryNotes[] = $note;

        return $this;
    }

    /**
     * Replace all delivery notes: ->deliveryNotes(['BL-1', ['number' => 'BL-2', 'date' => '2026-10-21']]).
     *
     * @param  array<int, Reference|array|string>  $notes
     */
    public function deliveryNotes(array $notes): static
    {
        $this->deliveryNotes = [];
        foreach ($notes as $note) {
            $this->deliveryNote($note);
        }

        return $this;
    }

    public function notes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function withStampDuty(StampDuty $stampDuty): static
    {
        $this->stampDuty = $stampDuty;

        return $this;
    }

    public function getType(): DocumentType
    {
        return $this->type;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    public function getDueDate(): ?DateTimeInterface
    {
        return $this->dueDate;
    }

    public function getSeller(): ?Party
    {
        return $this->seller;
    }

    public function getBuyer(): ?Party
    {
        return $this->buyer;
    }

    /** @return Line[] */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function getOriginalNumber(): ?string
    {
        return $this->originalNumber;
    }

    public function getOrder(): ?Reference
    {
        return $this->order;
    }

    /** The purchase order number. */
    public function getOrderReference(): ?string
    {
        return $this->order?->number;
    }

    /** @return Reference[] */
    public function getDeliveryNotes(): array
    {
        return $this->deliveryNotes;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * VAT is computed per rate on the sum of the lines at that rate, then rounded once.
     */
    public function totals(): Totals
    {
        $bases = [];
        foreach ($this->lines as $line) {
            $rate = (string) $line->vatRate;
            $bases[$rate] = ($bases[$rate] ?? 0) + $line->total();
        }
        krsort($bases, SORT_NUMERIC);

        $breakdown = [];
        foreach ($bases as $rate => $base) {
            $breakdown[(string) $rate] = ['base' => $base, 'vat' => (int) round($base * (float) $rate / 100)];
        }

        $totalHt = array_sum($bases);
        $totalVat = array_sum(array_column($breakdown, 'vat'));
        $totalTtc = $totalHt + $totalVat;

        $stampDuty = 0;
        if ($this->type === DocumentType::Invoice && $this->paymentMethod?->hasStampDuty()) {
            $config = (array) static::config('algeria-invoice.stamp_duty', []);
            $stamp = $this->stampDuty ?? new StampDuty($config);
            $stampDuty = $stamp->calculate(($config['base'] ?? 'ttc') === 'ht' ? $totalHt : $totalTtc);
        }

        return new Totals($totalHt, $breakdown, $totalVat, $totalTtc, $stampDuty, $totalTtc + $stampDuty);
    }

    /**
     * Missing or malformed mandatory mentions (decree 05-468), keyed by field. Empty when compliant.
     *
     * @return array<string, string>
     */
    public function errors(): array
    {
        return (new Checker)->check($this);
    }

    public function isCompliant(): bool
    {
        return $this->errors() === [];
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'number' => $this->number,
            'date' => $this->date->format('Y-m-d'),
            'due_date' => $this->dueDate?->format('Y-m-d'),
            'seller' => $this->seller?->toArray(),
            'buyer' => $this->buyer?->toArray(),
            'lines' => array_map(fn (Line $l) => $l->toArray(), $this->lines),
            'payment_method' => $this->paymentMethod?->value,
            'original_number' => $this->originalNumber,
            'order_reference' => $this->order?->number,
            'order_date' => $this->order?->date?->format('Y-m-d'),
            'delivery_notes' => array_map(fn (Reference $r) => $r->toArray(), $this->deliveryNotes),
            'notes' => $this->notes,
            'totals' => $this->totals()->toArray(),
        ];
    }

    /**
     * Read Laravel config when the package runs inside an application, defaults otherwise.
     */
    protected static function config(string $key, mixed $default = null): mixed
    {
        return function_exists('app') && app()->bound('config') ? config($key, $default) : $default;
    }

    /**
     * Rebuild an invoice saved with toArray() (totals are recomputed, not read).
     */
    public static function fromArray(array $data): static
    {
        return static::make()
            ->type($data['type'] ?? DocumentType::Invoice)
            ->number($data['number'] ?? null)
            ->date($data['date'] ?? 'today')
            ->dueDate($data['due_date'] ?? null)
            ->seller($data['seller'] ?? ['name' => ''])
            ->buyer($data['buyer'] ?? ['name' => ''])
            ->lines($data['lines'] ?? [])
            ->payment($data['payment_method'] ?? null)
            ->original($data['original_number'] ?? null)
            ->order($data['order_reference'] ?? null, $data['order_date'] ?? null)
            ->deliveryNotes($data['delivery_notes'] ?? [])
            ->notes($data['notes'] ?? null);
    }
}
