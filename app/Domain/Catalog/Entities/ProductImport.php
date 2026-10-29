<?php

namespace App\Domain\Catalog\Entities;

use App\Domain\Catalog\Enums\ImportStatus;
use App\Domain\Catalog\Exceptions\InvalidProduct;
use App\Domain\Catalog\ValueObjects\ImportId;
use DateTimeImmutable;

/**
 * A batch of products to create in the background. Rows are processed in
 * order; the progress is the index of the next row, so an interrupted
 * import resumes exactly where it stopped.
 */
final class ProductImport
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array{row: int|null, message: string}>  $errors
     */
    private function __construct(
        private ImportId $id,
        private int $ownerId,
        private array $rows,
        private ImportStatus $status,
        private int $processed,
        private int $succeeded,
        private array $errors,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $finishedAt,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public static function start(int $ownerId, array $rows): self
    {
        if ($rows === []) {
            throw new InvalidProduct('An import needs at least one row.');
        }

        return new self(ImportId::generate(), $ownerId, array_values($rows), ImportStatus::Pending, 0, 0, [], new DateTimeImmutable, null);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array{row: int|null, message: string}>  $errors
     */
    public static function reconstitute(
        ImportId $id,
        int $ownerId,
        array $rows,
        ImportStatus $status,
        int $processed,
        int $succeeded,
        array $errors,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $finishedAt,
    ): self {
        return new self($id, $ownerId, $rows, $status, $processed, $succeeded, $errors, $createdAt, $finishedAt);
    }

    /**
     * The next row to process, or null when every row was processed.
     *
     * @return array{index: int, data: array<string, mixed>}|null
     */
    public function nextRow(): ?array
    {
        if ($this->isFinished() || $this->processed >= $this->total()) {
            return null;
        }

        return ['index' => $this->processed, 'data' => $this->rows[$this->processed]];
    }

    public function recordSuccess(): void
    {
        $this->succeeded++;
        $this->advance();
    }

    public function recordFailure(string $message): void
    {
        $this->errors[] = ['row' => $this->processed, 'message' => $message];
        $this->advance();
    }

    /**
     * The import could not be processed at all (not a single row failing).
     */
    public function markFailed(string $reason): void
    {
        $this->errors[] = ['row' => null, 'message' => $reason];
        $this->status = ImportStatus::Failed;
        $this->finishedAt = new DateTimeImmutable;
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [ImportStatus::Completed, ImportStatus::Failed], true);
    }

    private function advance(): void
    {
        $this->processed++;
        $this->status = ImportStatus::Processing;

        if ($this->processed >= $this->total()) {
            $this->status = ImportStatus::Completed;
            $this->finishedAt = new DateTimeImmutable;
        }
    }

    public function id(): ImportId
    {
        return $this->id;
    }

    public function ownerId(): int
    {
        return $this->ownerId;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    public function status(): ImportStatus
    {
        return $this->status;
    }

    public function total(): int
    {
        return count($this->rows);
    }

    public function processed(): int
    {
        return $this->processed;
    }

    public function succeeded(): int
    {
        return $this->succeeded;
    }

    public function failed(): int
    {
        return count(array_filter($this->errors, fn (array $error) => $error['row'] !== null));
    }

    /**
     * @return list<array{row: int|null, message: string}>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function finishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }
}
