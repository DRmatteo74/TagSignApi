<?php

namespace App\Entity;

use App\Repository\ClasseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClasseRepository::class)]
class Classe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getClasses", "getCours", "getParticipe"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getClasses", "getCours", "getParticipe"])]
    #[Assert\NotBlank(message: "Le nom de la classe est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le nom de la classe doit faire au moins {{ limit }} caractères", maxMessage: "Le nom de la classe ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $nom = null;

    #[ORM\ManyToOne(inversedBy: 'classes')]
    #[Groups(["getClasses", "getCours", "getParticipe"])]
    private ?Ecole $ecole = null;

    #[ORM\ManyToMany(targetEntity: Utilisateurs::class, inversedBy: 'classes')]
    private Collection $utilisateurs;

    #[ORM\OneToMany(mappedBy: 'classe', targetEntity: Cours::class)]
    private Collection $cours;

    public function __construct()
    {
        $this->utilisateurs = new ArrayCollection();
        $this->cours = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEcole(): ?Ecole
    {
        return $this->ecole;
    }

    public function setEcole(?Ecole $ecole): static
    {
        $this->ecole = $ecole;

        return $this;
    }

    /**
     * @return Collection<int, Utilisateurs>
     */
    public function getUtilisateurs(): Collection
    {
        return $this->utilisateurs;
    }

    public function addUtilisateur(Utilisateurs $utilisateur): static
    {
        if (!$this->utilisateurs->contains($utilisateur)) {
            $this->utilisateurs->add($utilisateur);
        }

        return $this;
    }

    public function removeUtilisateur(Utilisateurs $utilisateur): static
    {
        $this->utilisateurs->removeElement($utilisateur);

        return $this;
    }

    /**
     * @return Collection<int, Cours>
     */
    public function getCours(): Collection
    {
        return $this->cours;
    }

    public function addCour(Cours $cour): static
    {
        if (!$this->cours->contains($cour)) {
            $this->cours->add($cour);
            $cour->setClasse($this);
        }

        return $this;
    }

    public function removeCour(Cours $cour): static
    {
        if ($this->cours->removeElement($cour)) {
            // set the owning side to null (unless already changed)
            if ($cour->getClasse() === $this) {
                $cour->setClasse(null);
            }
        }

        return $this;
    }
}
