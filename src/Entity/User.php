<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité User représentant un utilisateur du système
 * 
 * @author Votre Nom
 * @since 1.0.0
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'This email is already registered.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Email cannot be blank.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[Assert\Length(max: 180, maxMessage: 'Email cannot be longer than {{ limit }} characters.')]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'First name cannot be blank.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'First name must be at least {{ limit }} characters long.',
        maxMessage: 'First name cannot be longer than {{ limit }} characters.'
    )]
    private ?string $firstName = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Last name cannot be blank.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Last name must be at least {{ limit }} characters long.',
        maxMessage: 'Last name cannot be longer than {{ limit }} characters.'
    )]
    private ?string $lastName = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotBlank(message: 'Password cannot be blank.')]
    private ?string $password = null;

    /**
     * @var Collection<int, Client>
     */
    #[ORM\OneToMany(
        targetEntity: Client::class,
        mappedBy: 'user',
        orphanRemoval: true,
        cascade: ['persist', 'remove']
    )]
    private Collection $clients;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isVerified = false;

    /**
     * Constructeur de l'entité User
     */
    public function __construct()
    {
        $this->clients = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->roles = ['ROLE_USER'];
    }

    /**
     * Callback executed before persisting the entity
     */
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
        $this->updatedAt = new \DateTime();
    }

    /**
     * Callback executed before updating the entity
     */
    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // ==================== GETTERS & SETTERS ====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = strtolower(trim($email));
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = ucfirst(trim($firstName));
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = ucfirst(trim($lastName));
        return $this;
    }

    /**
     * Retourne le nom complet de l'utilisateur
     */
    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->firstName ?? '', $this->lastName ?? ''));
    }

    /**
     * Retourne les initiales de l'utilisateur
     */
    public function getInitials(): string
    {
        $firstInitial = $this->firstName ? substr($this->firstName, 0, 1) : '';
        $lastInitial = $this->lastName ? substr($this->lastName, 0, 1) : '';
        
        return strtoupper($firstInitial . $lastInitial);
    }

    // ==================== UserInterface Implementation ====================

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Guarantee every user at least has ROLE_USER
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        // Ensure ROLE_USER is always present
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }
        
        $this->roles = array_unique($roles);
        return $this;
    }

    /**
     * Ajoute un rôle à l'utilisateur
     */
    public function addRole(string $role): static
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
        
        return $this;
    }

    /**
     * Retire un rôle de l'utilisateur (sauf ROLE_USER)
     */
    public function removeRole(string $role): static
    {
        if ($role !== 'ROLE_USER') {
            $this->roles = array_values(array_filter($this->roles, fn($r) => $r !== $role));
        }
        
        return $this;
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    // ==================== PasswordAuthenticatedUserInterface Implementation ====================

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
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
        // Example: $this->plainPassword = null;
    }

    // ==================== Clients Management ====================

    /**
     * @return Collection<int, Client>
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    /**
     * Retourne les clients actifs de l'utilisateur
     * 
     * @return Collection<int, Client>
     */
    public function getActiveClients(): Collection
    {
        return $this->clients->filter(fn(Client $client) => $client->isActive());
    }

    /**
     * Retourne le nombre total de clients
     */
    public function getClientsCount(): int
    {
        return $this->clients->count();
    }

    /**
     * Retourne le nombre de clients actifs
     */
    public function getActiveClientsCount(): int
    {
        return $this->getActiveClients()->count();
    }

    public function addClient(Client $client): static
    {
        if (!$this->clients->contains($client)) {
            $this->clients->add($client);
            $client->setUser($this);
        }

        return $this;
    }

    public function removeClient(Client $client): static
    {
        // CORRIGÉ: Fusion des conditions if imbriquées pour une meilleure lisibilité
        if ($this->clients->removeElement($client) && $client->getUser() === $this) {
            $client->setUser(null);
        }

        return $this;
    }

    // ==================== Timestamps Management ====================

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    /**
     * Met à jour la date de dernière connexion
     */
    public function updateLastLogin(): static
    {
        $this->lastLoginAt = new \DateTime();
        return $this;
    }

    // ==================== Status Management ====================

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * Active l'utilisateur
     */
    public function activate(): static
    {
        $this->isActive = true;
        return $this;
    }

    /**
     * Désactive l'utilisateur
     */
    public function deactivate(): static
    {
        $this->isActive = false;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    /**
     * Marque l'utilisateur comme vérifié
     */
    public function markAsVerified(): static
    {
        $this->isVerified = true;
        return $this;
    }

    // ==================== Utility Methods ====================

    /**
     * Représentation string de l'utilisateur
     */
    public function __toString(): string
    {
        return $this->getFullName() ?: $this->getUserIdentifier();
    }

    /**
     * Sérialise l'utilisateur pour les sessions
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'password' => $this->password,
            'isActive' => $this->isActive,
        ];
    }

    /**
     * Désérialise l'utilisateur depuis les sessions
     */
    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->isActive = $data['isActive'];
    }

    /**
     * Vérifie si l'utilisateur est un administrateur
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    /**
     * Vérifie si l'utilisateur est un super administrateur
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('ROLE_SUPER_ADMIN');
    }

    /**
     * Retourne l'âge du compte en jours
     */
    public function getAccountAge(): int
    {
        if (!$this->createdAt) {
            return 0;
        }
        
        return (int) $this->createdAt->diff(new \DateTime())->days;
    }

    /**
     * Vérifie si l'utilisateur s'est connecté récemment
     */
    public function hasRecentLogin(int $days = 7): bool
    {
        if (!$this->lastLoginAt) {
            return false;
        }
        
        $threshold = new \DateTime(sprintf('-%d days', $days));
        return $this->lastLoginAt >= $threshold;
    }
}

// ============================================================================
// src/Repository/ClientRepository.php - CODE COMPLET AVEC TOUTES LES MÉTHODES
// ============================================================================

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Client
 * 
 * @extends ServiceEntityRepository<Client>
 * 
 * @method Client|null find($id, $lockMode = null, $lockVersion = null)
 * @method Client|null findOneBy(array $criteria, array $orderBy = null)
 * @method Client[]    findAll()
 * @method Client[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * Sauvegarde un client en base de données
     */
    public function save(Client $client, bool $flush = false): void
    {
        $this->getEntityManager()->persist($client);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un client de la base de données
     */
    public function remove(Client $client, bool $flush = false): void
    {
        $this->getEntityManager()->remove($client);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // ==================== Recherches de base ====================

    /**
     * Trouve les clients d'un utilisateur spécifique
     * 
     * @return Client[] Returns an array of Client objects
     */
    public function findByUser(User|int $user): array
    {
        $userId = $user instanceof User ? $user->getId() : $user;
        
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un client par son email
     */
    public function findOneByEmail(string $email): ?Client
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.email = :email')
            ->setParameter('email', strtolower(trim($email)))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les clients actifs d'un utilisateur
     * 
     * @return Client[]
     */
    public function findActiveByUser(User|int $user): array
    {
        $userId = $user instanceof User ? $user->getId() : $user;
        
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :userId')
            ->andWhere('c.isActive = :active')
            ->setParameter('userId', $userId)
            ->setParameter('active', true)
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les clients par nom (recherche partielle)
     * 
     * @return Client[]
     */
    public function findByName(string $searchTerm, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('c');
        
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->like('c.firstName', ':searchTerm'),
                $qb->expr()->like('c.lastName', ':searchTerm'),
                $qb->expr()->like(
                    $qb->expr()->concat('c.firstName', $qb->expr()->concat($qb->expr()->literal(' '), 'c.lastName')),
                    ':searchTerm'
                )
            )
        )
        ->setParameter('searchTerm', '%' . $searchTerm . '%');
        
        if ($user) {
            $qb->andWhere('c.user = :user')
               ->setParameter('user', $user);
        }
        
        return $qb->orderBy('c.lastName', 'ASC')
                  ->addOrderBy('c.firstName', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    // ==================== Recherches avancées ====================

    /**
     * Recherche globale de clients avec critères multiples
     * 
     * @param array<string, mixed> $criteria
     * @return Client[]
     */
    public function search(array $criteria = [], ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('c');
        
        if ($user) {
            $qb->andWhere('c.user = :user')
               ->setParameter('user', $user);
        }
        
        // Recherche par nom
        if (!empty($criteria['name'])) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('c.firstName', ':name'),
                    $qb->expr()->like('c.lastName', ':name'),
                    $qb->expr()->like(
                        $qb->expr()->concat('c.firstName', $qb->expr()->concat($qb->expr()->literal(' '), 'c.lastName')),
                        ':name'
                    )
                )
            )
            ->setParameter('name', '%' . $criteria['name'] . '%');
        }
        
        // Recherche par email
        if (!empty($criteria['email'])) {
            $qb->andWhere('c.email LIKE :email')
               ->setParameter('email', '%' . $criteria['email'] . '%');
        }
        
        // Filtrage par statut actif
        if (isset($criteria['isActive'])) {
            $qb->andWhere('c.isActive = :isActive')
               ->setParameter('isActive', (bool) $criteria['isActive']);
        }
        
        // Filtrage par ville
        if (!empty($criteria['city'])) {
            $qb->andWhere('c.city LIKE :city')
               ->setParameter('city', '%' . $criteria['city'] . '%');
        }
        
        // Filtrage par date de création
        if (!empty($criteria['createdAfter'])) {
            $qb->andWhere('c.createdAt >= :createdAfter')
               ->setParameter('createdAfter', $criteria['createdAfter']);
        }
        
        if (!empty($criteria['createdBefore'])) {
            $qb->andWhere('c.createdAt <= :createdBefore')
               ->setParameter('createdBefore', $criteria['createdBefore']);
        }
        
        // Tri
        $sortField = $criteria['sortField'] ?? 'lastName';
        $sortDirection = $criteria['sortDirection'] ?? 'ASC';
        
        $allowedSortFields = ['firstName', 'lastName', 'email', 'createdAt', 'city'];
        if (in_array($sortField, $allowedSortFields, true)) {
            $qb->orderBy('c.' . $sortField, $sortDirection);
            
            if ($sortField !== 'lastName') {
                $qb->addOrderBy('c.lastName', 'ASC');
            }
            if ($sortField !== 'firstName') {
                $qb->addOrderBy('c.firstName', 'ASC');
            }
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche paginée de clients
     * 
     * @param array<string, mixed> $criteria
     */
    public function searchPaginated(array $criteria = [], int $page = 1, int $limit = 20, ?User $user = null): Paginator
    {
        $qb = $this->createQueryBuilder('c');
        
        if ($user) {
            $qb->andWhere('c.user = :user')
               ->setParameter('user', $user);
        }
        
        // Application des mêmes critères que la méthode search
        $this->applyCriteria($qb, $criteria);
        
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);
        
        return new Paginator($qb->getQuery());
    }

    // ==================== Statistiques ====================

    /**
     * Compte le nombre total de clients d'un utilisateur
     */
    public function countByUser(User|int $user): int
    {
        $userId = $user instanceof User ? $user->getId() : $user;
        
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte le nombre de clients actifs d'un utilisateur
     */
    public function countActiveByUser(User|int $user): int
    {
        $userId = $user instanceof User ? $user->getId() : $user;
        
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.user = :userId')
            ->andWhere('c.isActive = :active')
            ->setParameter('userId', $userId)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Retourne les statistiques de clients par mois pour un utilisateur
     * 
     * @return array<array{month: string, count: int}>
     */
    public function getMonthlyStats(User|int $user, int $year): array
    {
        $userId = $user instanceof User ? $user->getId() : $user;
        
        $result = $this->createQueryBuilder('c')
            ->select('MONTH(c.createdAt) as month, COUNT(c.id) as count')
            ->andWhere('c.user = :userId')
            ->andWhere('YEAR(c.createdAt) = :year')
            ->setParameter('userId', $userId)
            ->setParameter('year', $year)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Compléter les mois manquants avec 0
        $stats = [];
        for ($month = 1; $month <= 12; $month++) {
            $stats[] = [
                'month' => sprintf('%04d-%02d', $year, $month),
                'count' => 0
            ];
        }
        
        foreach ($result as $row) {
            $monthIndex = (int) $row['month'] - 1;
            $stats[$monthIndex]['count'] = (int) $row['count'];
        }
        
        return $stats;
    }
