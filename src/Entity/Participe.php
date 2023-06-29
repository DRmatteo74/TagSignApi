<?php

namespace App\Entity;

use App\Repository\ParticipeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipeRepository::class)]
class Participe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participes')]
    private ?Cours $cours = null;

    #[ORM\ManyToOne(inversedBy: 'participes')]
    private ?Utilisateurs $utilisateur = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le booléen presence est requis.")]
    private ?bool $presence = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heure_badgeage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCours(): ?Cours
    {
        return $this->cours;
    }

    public function setCours(?Cours $cours): static
    {
        $this->cours = $cours;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateurs
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateurs $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function isPresence(): ?bool
    {
        return $this->presence;
    }

    public function setPresence(bool $presence): static
    {
        $this->presence = $presence;

        return $this;
    }

    public function getHeureBadgeage(): ?\DateTimeInterface
    {
        return $this->heure_badgeage;
    }

    public function setHeureBadgeage(?\DateTimeInterface $heure_badgeage): static
    {
        $this->heure_badgeage = $heure_badgeage;

        return $this;
    }
}
