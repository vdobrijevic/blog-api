<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"user:read"}},
 *     denormalizationContext={"groups"={"user:write"}},
 *     itemOperations={
 *         "get",
 *         "put"={"security"="is_granted('USER_EDIT', object)"},
 *     },
 *     collectionOperations={
 *         "get"={"security"="is_granted('ROLE_ADMIN')"},
 *         "post"
 *     }
 * )
 * @UniqueEntity(fields={"email"})
 *
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     *
     * @Groups({"user:read", "user:write", "verification_request:read"})
     *
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     *
     * @Groups({"superadmin:write"})
     */
    private $roles = [];

    /**
     * @var string The hashed password
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"user:write"})
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=50)
     *
     * @Groups({"user:read", "user:write", "verification_request:read"})
     *
     * @Assert\NotBlank()
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=50)
     *
     * @Groups({"user:read", "user:write", "verification_request:read"})
     *
     * @Assert\NotBlank()
     */
    private $lastName;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $created;

    /**
     * @ORM\OneToMany(targetEntity=BlogPost::class, mappedBy="owner", orphanRemoval=true)
     *
     * @Groups({"user:read"})
     */
    private $blogPosts;

    /**
     * @ORM\OneToMany(targetEntity=VerificationRequest::class, mappedBy="owner", orphanRemoval=true)
     *
     * @Groups({"user:read"})
     */
    private $verificationRequests;

    public function __construct(string $email, string $password, string $firstName, string $lastName)
    {
        $this->email = trim($email);
        $this->password = password_hash(trim($password), PASSWORD_DEFAULT);
        $this->firstName = trim($firstName);
        $this->lastName = trim($lastName);
        $this->roles = ['ROLE_USER'];
        $this->created = new \DateTimeImmutable();
        $this->blogPosts = new ArrayCollection();
        $this->verificationRequests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        // guarantee every user at least has ROLE_USER
        if (empty($this->roles)) {
            return ['ROLE_USER'];
        }

        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * @return Collection|BlogPost[]
     */
    public function getBlogPosts(): Collection
    {
        return $this->blogPosts;
    }

    public function addBlogPost(BlogPost $blogPost): self
    {
        if (!$this->blogPosts->contains($blogPost)) {
            $this->blogPosts[] = $blogPost;
            $blogPost->setOwner($this);
        }

        return $this;
    }

    public function removeBlogPost(BlogPost $blogPost): self
    {
        if ($this->blogPosts->contains($blogPost)) {
            $this->blogPosts->removeElement($blogPost);
            // set the owning side to null (unless already changed)
            if ($blogPost->getOwner() === $this) {
                $blogPost->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|VerificationRequest[]
     */
    public function getVerificationRequests(): Collection
    {
        return $this->verificationRequests;
    }

    public function addVerificationRequest(VerificationRequest $verificationRequest): self
    {
        if (!$this->verificationRequests->contains($verificationRequest)) {
            $this->verificationRequests[] = $verificationRequest;
            $verificationRequest->setOwner($this);
        }

        return $this;
    }

    public function removeVerificationRequest(VerificationRequest $verificationRequest): self
    {
        if ($this->verificationRequests->contains($verificationRequest)) {
            $this->verificationRequests->removeElement($verificationRequest);
            // set the owning side to null (unless already changed)
            if ($verificationRequest->getOwner() === $this) {
                $verificationRequest->setOwner(null);
            }
        }

        return $this;
    }
}
