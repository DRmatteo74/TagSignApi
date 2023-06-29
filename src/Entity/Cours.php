<?php

namespace App\Entity;

use App\Repository\CoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CoursRepository::class)]
class Cours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getCours"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getCours"])]
    #[Assert\NotBlank(message: "Le nom du cours est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le nom du coursdoit faire au moins {{ limit }} caractères", maxMessage: "Le nom du coursne peut pas faire plus de {{ limit }} caractères")]
    private ?string $nom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(["getCours"])]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(["getCours"])]
    private ?\DateTimeInterface $heure = null;

    #[ORM\Column]
    #[Groups(["getCours"])]
    #[Assert\NotBlank(message: "Le booléen distanciel est obligatoire")]
    private ?bool $distanciel = null;

    #[ORM\ManyToOne(inversedBy: 'cours')]
    #[Groups(["getCours"])]
    private ?Salle $salle = null;

    #[ORM\ManyToOne(inversedBy: 'cours')]
    #[Groups(["getCours"])]
    private ?Classe $classe = null;

    #[ORM\OneToMany(mappedBy: 'cours', targetEntity: Participe::class)]
    private Collection $participes;

    public function __construct()
    {
        $this->participes = new ArrayCollection();
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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getHeure(): ?\DateTimeInterface
    {
        return $this->heure;
    }

    public function setHeure(?\DateTimeInterface $heure): static
    {
        $this->heure = $heure;

        return $this;
    }

    public function isDistanciel(): ?bool
    {
        return $this->distanciel;
    }

    public function setDistanciel(bool $distanciel): static
    {
        $this->distanciel = $distanciel;

        return $this;
    }

    public function getSalle(): ?Salle
    {
        return $this->salle;
    }

    public function setSalle(?Salle $salle): static
    {
        $this->salle = $salle;

        return $this;
    }

    public function getClasse(): ?Classe
    {
        return $this->classe;
    }

    public function setClasse(?Classe $classe): static
    {
        $this->classe = $classe;

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
            $participe->setCours($this);
        }

        return $this;
    }

    public function removeParticipe(Participe $participe): static
    {
        if ($this->participes->removeElement($participe)) {
            // set the owning side to null (unless already changed)
            if ($participe->getCours() === $this) {
                $participe->setCours(null);
            }
        }

        return $this;
    }
}
