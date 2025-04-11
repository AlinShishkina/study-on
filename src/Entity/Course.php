<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
#[ORM\Table(name: 'course')] // добавлено имя таблицы для явности
#[UniqueEntity('characterCode', message: "Курс с таким кодом уже существует.")]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank(message: "Код курса не может быть пустым.")]
    #[Assert\Length(min: 3, max: 255, minMessage: "Код курса должен содержать хотя бы {{ limit }} символов.", maxMessage: "Код курса не может быть длиннее {{ limit }} символов.")]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9-_]+$/',
        message: 'Код курса может содержать только буквы, цифры, дефисы и подчеркивания.'
    )]
    private ?string $characterCode = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Название курса не может быть пустым.")]
    #[Assert\Length(min: 3, max: 255, minMessage: "Название курса должно содержать хотя бы {{ limit }} символов.", maxMessage: "Название курса не может быть длиннее {{ limit }} символов.")]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 1000, nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: "Описание не может быть длиннее {{ limit }} символов.")]
    private ?string $description = null;

    #[ORM\OneToMany(targetEntity: Lesson::class, mappedBy: 'course', cascade: ['remove'], orphanRemoval: true)]
    private Collection $lessons;

    public function __construct()
    {
        $this->lessons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCharacterCode(): ?string
    {
        return $this->characterCode;
    }

    public function setCharacterCode(string $characterCode): self
    {
        $this->characterCode = $characterCode;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    // Функция для добавления уроков в курс
    public function addLesson(Lesson $lesson): self
    {
        if (!$this->lessons->contains($lesson)) {
            $this->lessons[] = $lesson;
            $lesson->setCourse($this);
        }

        return $this;
    }

    // Функция для удаления урока из курса
    public function removeLesson(Lesson $lesson): self
    {
        if ($this->lessons->removeElement($lesson)) {
            // Устанавливаем null для связи с курсом в удаляемом уроке
            if ($lesson->getCourse() === $this) {
                $lesson->setCourse(null);
            }
        }

        return $this;
    }
}
