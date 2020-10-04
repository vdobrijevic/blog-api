<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\BlogPostRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     itemOperations={
 *         "get",
 *         "delete"={"security"="is_granted('POST_DELETE', object)"},
 *         "put"={"security"="is_granted('POST_EDIT', object)"},
 *     },
 *     collectionOperations={
 *         "get",
 *         "post"={"security"="is_granted('ROLE_BLOGGER')"}
 *     }
 * )
 *
 * @ORM\Entity(repositoryClass=BlogPostRepository::class)
 */
class BlogPost
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotBlank()
     * @Assert\Length(
     *     min=10,
     *     max=50,
     *     minMessage="Titles must be at least 10 chars long",
     *     maxMessage="Titles must not be more than 50 chars long"
     * )
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     *
     * @Assert\NotBlank()
     */
    private $content;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $created;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="blogPosts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $owner;

    public function __construct(string $title, string $content, User $owner)
    {
        $this->title = trim($title);
        $this->content = trim($content);
        $this->owner = $owner;
        $this->created = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = trim($content);

        return $this;
    }

    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }
}
