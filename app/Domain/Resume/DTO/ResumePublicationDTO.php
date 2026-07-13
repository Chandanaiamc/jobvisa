<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\DTO;

/**
 * Resume publication / research record.
 */
final class ResumePublicationDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $resumeId,
        public readonly ?int $projectId,
        public readonly ?string $projectTitle,
        public readonly ?int $countryId,
        public readonly ?string $countryName,
        public readonly ?int $cityId,
        public readonly ?string $cityName,
        public readonly string $title,
        public readonly string $publicationType,
        public readonly ?string $publisher,
        public readonly ?string $authors,
        public readonly ?string $userContribution,
        public readonly ?string $publicationDate,
        public readonly ?int $publicationYear,
        public readonly ?string $volume,
        public readonly ?string $issue,
        public readonly ?string $pageRange,
        public readonly ?string $doi,
        public readonly ?string $isbn,
        public readonly ?string $issn,
        public readonly ?string $patentNumber,
        public readonly ?string $conferenceName,
        public readonly ?string $abstractSummary,
        public readonly ?string $keywords,
        public readonly ?string $publicationUrl,
        public readonly ?string $documentPath,
        public readonly ?string $documentOriginalName,
        public readonly ?string $documentMime,
        public readonly ?int $documentSize,
        public readonly bool $isPeerReviewed,
        public readonly bool $isFeatured,
        public readonly string $visibility,
        public readonly string $status,
        public readonly int $sortOrder,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly ?string $deletedAt,
        public readonly bool $canEdit,
    ) {
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromRow(array $row, bool $canEdit): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            resumeId: (int) ($row['resume_id'] ?? 0),
            projectId: self::nullId($row['project_id'] ?? null),
            projectTitle: self::nullStr($row['project_title'] ?? null),
            countryId: self::nullId($row['country_id'] ?? null),
            countryName: self::nullStr($row['country_name'] ?? null),
            cityId: self::nullId($row['city_id'] ?? null),
            cityName: self::nullStr($row['city_name'] ?? null),
            title: trim((string) ($row['title'] ?? '')),
            publicationType: (string) ($row['publication_type'] ?? 'other'),
            publisher: self::nullStr($row['publisher'] ?? null),
            authors: self::nullStr($row['authors'] ?? null),
            userContribution: self::nullStr($row['user_contribution'] ?? null),
            publicationDate: self::nullStr($row['publication_date'] ?? null),
            publicationYear: isset($row['publication_year']) && $row['publication_year'] !== null && $row['publication_year'] !== ''
                ? (int) $row['publication_year']
                : null,
            volume: self::nullStr($row['volume'] ?? null),
            issue: self::nullStr($row['issue'] ?? null),
            pageRange: self::nullStr($row['page_range'] ?? null),
            doi: self::nullStr($row['doi'] ?? null),
            isbn: self::nullStr($row['isbn'] ?? null),
            issn: self::nullStr($row['issn'] ?? null),
            patentNumber: self::nullStr($row['patent_number'] ?? null),
            conferenceName: self::nullStr($row['conference_name'] ?? null),
            abstractSummary: self::nullStr($row['abstract_summary'] ?? null),
            keywords: self::nullStr($row['keywords'] ?? null),
            publicationUrl: self::nullStr($row['publication_url'] ?? null),
            documentPath: self::nullStr($row['document_path'] ?? null),
            documentOriginalName: self::nullStr($row['document_original_name'] ?? null),
            documentMime: self::nullStr($row['document_mime'] ?? null),
            documentSize: isset($row['document_size']) && $row['document_size'] !== null && $row['document_size'] !== ''
                ? (int) $row['document_size']
                : null,
            isPeerReviewed: !empty($row['is_peer_reviewed']),
            isFeatured: !empty($row['is_featured']),
            visibility: (string) ($row['visibility'] ?? 'public'),
            status: (string) ($row['status'] ?? 'active'),
            sortOrder: (int) ($row['sort_order'] ?? 0),
            createdAt: self::nullStr($row['created_at'] ?? null),
            updatedAt: self::nullStr($row['updated_at'] ?? null),
            deletedAt: self::nullStr($row['deleted_at'] ?? null),
            canEdit: $canEdit,
        );
    }

    public static function blank(int $resumeId, bool $canEdit): self
    {
        return new self(
            id: null,
            resumeId: $resumeId,
            projectId: null,
            projectTitle: null,
            countryId: null,
            countryName: null,
            cityId: null,
            cityName: null,
            title: '',
            publicationType: '',
            publisher: null,
            authors: null,
            userContribution: null,
            publicationDate: null,
            publicationYear: null,
            volume: null,
            issue: null,
            pageRange: null,
            doi: null,
            isbn: null,
            issn: null,
            patentNumber: null,
            conferenceName: null,
            abstractSummary: null,
            keywords: null,
            publicationUrl: null,
            documentPath: null,
            documentOriginalName: null,
            documentMime: null,
            documentSize: null,
            isPeerReviewed: false,
            isFeatured: false,
            visibility: 'public',
            status: 'active',
            sortOrder: 0,
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
            canEdit: $canEdit,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormArray(): array
    {
        return [
            'title' => $this->title,
            'publication_type' => $this->publicationType,
            'publisher' => $this->publisher ?? '',
            'authors' => $this->authors ?? '',
            'user_contribution' => $this->userContribution ?? '',
            'publication_date' => $this->publicationDate ?? '',
            'publication_year' => $this->publicationYear !== null ? (string) $this->publicationYear : '',
            'volume' => $this->volume ?? '',
            'issue' => $this->issue ?? '',
            'page_range' => $this->pageRange ?? '',
            'doi' => $this->doi ?? '',
            'isbn' => $this->isbn ?? '',
            'issn' => $this->issn ?? '',
            'patent_number' => $this->patentNumber ?? '',
            'conference_name' => $this->conferenceName ?? '',
            'abstract_summary' => $this->abstractSummary ?? '',
            'keywords' => $this->keywords ?? '',
            'publication_url' => $this->publicationUrl ?? '',
            'project_id' => $this->projectId !== null ? (string) $this->projectId : '',
            'country_id' => $this->countryId !== null ? (string) $this->countryId : '',
            'city_id' => $this->cityId !== null ? (string) $this->cityId : '',
            'is_peer_reviewed' => $this->isPeerReviewed ? '1' : '',
            'is_featured' => $this->isFeatured ? '1' : '',
            'visibility' => $this->visibility,
            'status' => $this->status,
            'sort_order' => (string) $this->sortOrder,
        ];
    }

    /**
     * Public profile projection. Only public + active rows.
     * Document path and metadata never exposed.
     *
     * @return array<string, mixed>|null
     */
    public function toPublicArray(): ?array
    {
        if ($this->visibility !== 'public' || $this->deletedAt !== null || $this->status !== 'active') {
            return null;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'publication_type' => $this->publicationType,
            'publisher' => $this->publisher,
            'authors' => $this->authors,
            'user_contribution' => $this->userContribution,
            'publication_date' => $this->publicationDate,
            'publication_year' => $this->publicationYear,
            'volume' => $this->volume,
            'issue' => $this->issue,
            'page_range' => $this->pageRange,
            'doi' => $this->doi,
            'isbn' => $this->isbn,
            'issn' => $this->issn,
            'patent_number' => $this->patentNumber,
            'conference_name' => $this->conferenceName,
            'abstract_summary' => $this->abstractSummary,
            'keywords' => $this->keywords,
            'publication_url' => $this->publicationUrl,
            'project_id' => $this->projectId,
            'project_title' => $this->projectTitle,
            'country_name' => $this->countryName,
            'city_name' => $this->cityName,
            'is_peer_reviewed' => $this->isPeerReviewed,
            'is_featured' => $this->isFeatured,
            'has_document' => $this->documentPath !== null && $this->documentPath !== '',
            'sort_order' => $this->sortOrder,
        ];
    }

    public function hasDocument(): bool
    {
        return $this->documentPath !== null && $this->documentPath !== '';
    }

    private static function nullId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private static function nullStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
