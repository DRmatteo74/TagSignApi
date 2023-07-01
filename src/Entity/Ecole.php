<?php

namespace App\Entity;

use App\Repository\EcoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EcoleRepository::class)]
class Ecole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getClasses", "getEcole", "getCours", "getParticipe"])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(["getClasses", "getEcole", "getCours", "getParticipe"])]
    #[Assert\NotBlank(message: "Le nom de l'école est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le nom de l'école' doit faire au moins {{ limit }} caractères", maxMessage: "Le nom d'école ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $nom = null;

    #[ORM\OneToMany(mappedBy: 'ecole', targetEntity: Classe::class)]
    private Collection $classes;

    public function __construct()
    {
        $this->classes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
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
            $class->setEcole($this);
        }

        return $this;
    }

    public function removeClass(Classe $class): static
    {
        if ($this->classes->removeElement($class)) {
            // set the owning side to null (unless already changed)
            if ($class->getEcole() === $this) {
                $class->setEcole(null);
            }
        }

        return $this;
    }
}
