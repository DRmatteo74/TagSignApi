<?php

namespace App\Entity;

use App\Repository\UtilisateursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateursRepository::class)]
class Utilisateurs implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getUser", "getParticipe", "getClasses"])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(["getUser"])]
    #[Assert\NotBlank(message: "Le login de l'utilisateur est obligatoire")]
    #[Assert\Length(min: 1, max: 180, minMessage: "Le nom de l'utilisateur doit faire au moins {{ limit }} caractères", maxMessage: "Le nom de l'utilisateur ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $login = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUser", "getParticipe", "getClasses"])]
    #[Assert\NotBlank(message: "Le nom de l'utilisateur est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le nom de l'utilisateur doit faire au moins {{ limit }} caractères", maxMessage: "Le nom de l'utilisateur ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUser", "getParticipe", "getClasses"])]
    #[Assert\NotBlank(message: "Le prenom de l'utilisateur est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le prenom de l'utilisateur doit faire au moins {{ limit }} caractères", maxMessage: "Le nom de l'utilisateur ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $prenom = null;


    #[ORM\Column]
    #[Groups(["getUser"])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\NotBlank(message: "Le mot de passe de l'utilisateur est obligatoire")]
    private ?string $password = null;

    #[ORM\ManyToMany(targetEntity: Classe::class, mappedBy: 'utilisateurs')]
    private Collection $classes;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["getUser", "getParticipe"])]
    private ?string $badge = null;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Participe::class)]
    private Collection $participes;

    public function __construct()
    {
        $this->classes = new ArrayCollection();
        $this->participes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->login;
    }

     /**
     * Méthode getUsername qui permet de retourner le champ qui est utilisé pour l'authentification.
     *
     * @return string
     */
    public function getUsername(): string {
        return $this->getUserIdentifier();
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Classe>
     */
    public function getClasses(): Collection
    {
        return $this->classes;
    }

    public function addClass(Classe $class): static
    {
        if (!$this->classes->contains($class)) {
            $this->classes->add($class);
            $class->addUtilisateur($this);
        }

        return $this;
    }

    public function removeClass(Classe $class): static
    {
        if ($this->classes->removeElement($class)) {
            $class->removeUtilisateur($this);
        }

        return $this;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function setBadge(?string $badge): static
    {
        $this->badge = $badge;

        return $this;
    }

    /**
     * @return Collection<int, Participe>
     */
    public function getParticipes(): Collection
    {
        return $this->participes;
    }

    public function addParticipe(Participe $participe): static
    {
        if (!$this->participes->contains($participe)) {
            $this->participes->add($participe);
            $participe->setUtilisateur($this);
        }

        return $this;
    }

    public function removeParticipe(Participe $participe): static
    {
        if ($this->participes->removeElement($participe)) {
            // set the owning side to null (unless already changed)
            if ($participe->getUtilisateur() === $this) {
                $participe->setUtilisateur(null);
            }
        }

        return $this;
    }


}
