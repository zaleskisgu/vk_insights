<?php

namespace App\Data\Post;

readonly class PostListItemData
{
    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            post_id: (int) ($row['post_id'] ?? 0),
            owner_id: (int) ($row['owner_id'] ?? 0),
            date: (string) ($row['date'] ?? ''),
            type: (string) ($row['type'] ?? ''),
            label: (string) ($row['label'] ?? ''),
            text: (string) ($row['text'] ?? ''),
            likes: (int) ($row['likes'] ?? 0),
            comments: (int) ($row['comments'] ?? 0),
            reposts: (int) ($row['reposts'] ?? 0),
            engagement: (int) ($row['engagement'] ?? 0),
        );
    }

    public function __construct(
        public int $post_id,
        public int $owner_id,
        public string $date,
        public string $type,
        public string $label,
        public string $text,
        public int $likes,
        public int $comments,
        public int $reposts,
        public int $engagement,
    ) {}

    /**
     * @return array{
     *     post_id: int,
     *     owner_id: int,
     *     date: string,
     *     type: string,
     *     label: string,
     *     text: string,
     *     likes: int,
     *     comments: int,
     *     reposts: int,
     *     engagement: int
     * }
     */
    public function toArray(): array
    {
        return [
            'post_id' => $this->post_id,
            'owner_id' => $this->owner_id,
            'date' => $this->date,
            'type' => $this->type,
            'label' => $this->label,
            'text' => $this->text,
            'likes' => $this->likes,
            'comments' => $this->comments,
            'reposts' => $this->reposts,
            'engagement' => $this->engagement,
        ];
    }
}
