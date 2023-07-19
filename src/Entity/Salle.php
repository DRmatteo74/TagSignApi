<?php

namespace App\Entity;

use App\Repository\SalleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SalleRepository::class)]
class Salle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getSalles", "getCours", "getParticipe"])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable:false)]
    #[Groups(["getSalles", "getCours", "getParticipe"])]
    #[Assert\NotBlank(message: "Le nom de la salle est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le nom de la salle doit faire au moins {{ limit }} caractères", maxMessage: "Le nom de la classe ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $salle = null;

    #[ORM\Column(length: 255, nullable:true)]
    #[Groups(["getSalles", "getCours", "getParticipe"])]
    private ?string $lecteur = null;

    #[ORM\OneToMany(mappedBy: 'salle', targetEntity: Cours::class)]
    private Collection $cours;

    public function __construct()
    {
        $this->cours = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLecteur(): ?string
    {
        return $this->lecteur;
    }

    public function setLecteur(?string $lecteur): self
    {
        $this->lecteur = $lecteur;

        return $this;
    }

    public function getSalle(): ?string
    {
        return $this->salle;
    }

    public function setSalle(?string $salle): self
    {
        $this->salle = $salle;

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
            $cour->setSalle($this);
        }

        return $this;
    }

    public function removeCour(Cours $cour): static
    {
        if ($this->cours->removeElement($cour)) {
            // set the owning side to null (unless already changed)
            if ($cour->getSalle() === $this) {
                $cour->setSalle(null);
            }
        }

        return $this;
    }
}
