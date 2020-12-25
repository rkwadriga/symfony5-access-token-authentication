<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\TokenRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Helpers\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=TokenRepository::class)
 * @ORM\Table(name="token", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="user_id_access_token_uniq", columns={"user_id", "access_token"})
 * }, indexes={
 *      @ORM\Index(name="access_token_index", columns={"access_token"})
 * })
 * @ORM\HasLifecycleCallbacks()
 */
class Token implements TokenInterface
{
    use TimestampableEntity;

    private string $dateTimeFormat = 'Y-m-d H:i:s';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=64)
     * @Assert\NotBlank
     */
    private string $access_token;

    /**
     * @ORM\Column(type="string", length=64)
     * @Assert\NotBlank
     */
    private string $refresh_token;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank
     */
    private \DateTime $expired_at;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="tokens")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $user;

    private ?bool $_isAuthenticated;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccessToken(): string
    {
        return $this->access_token;
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->access_token = $accessToken;
        return $this;
    }

    public function getRefreshToken(): string
    {
        return $this->refresh_token;
    }

    public function setRefreshToken(string $refreshToken): self
    {
        $this->refresh_token = $refreshToken;
        return $this;
    }

    public function getExpiredAt(): \DateTime
    {
        return $this->expired_at;
    }

    public function setExpiredAt(\DateTime $expiredAt): self
    {
        $this->expired_at = $expiredAt;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser($user): self
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
        return $this;
    }

    public function __toString(): string
    {
        return (string)$this->access_token;
    }

    public function getRoleNames(): array
    {
        return ['ROL_USER'];
    }

    public function getCredentials(): array
    {
        return $this->__serialize();
    }

    public function getUsername(): ?string
    {
        return $this->user !== null ? $this->user->getUsername() : null;
    }

    public function isAuthenticated()
    {
        if ($this->_isAuthenticated !== null) {
            $this->_isAuthenticated;
        }
        return $this->_isAuthenticated = $this->user !== null && $this->expired_at > (new \DateTime());
    }

    public function setAuthenticated(bool $isAuthenticated)
    {
        $this->_isAuthenticated = $isAuthenticated;
    }

    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getAttributes()
    {
        return array_merge($this->__serialize(), ['created_at' => $this->createdAt, 'updated_at' => $this->updatedAt]);
    }

    public function setAttributes(array $attributes)
    {
        $this->__unserialize($attributes);

        if (isset($data['created_at'])) {
            if ($data['created_at'] instanceof \DateTime) {
                $this->created_at = $data['created_at'];
            } elseif (is_string($data['created_at'])) {
                $this->created_at = new \DateTime($data['created_at']);
            }
        }
        if (isset($data['updated_at'])) {
            if ($data['updated_at'] instanceof \DateTime) {
                $this->updated_at = $data['updated_at'];
            } elseif (is_string($data['updated_at'])) {
                $this->created_at = new \DateTime($data['updated_at']);
            }
        }
    }

    public function hasAttribute(string $name)
    {
        $getter = 'get' . ucfirst($name);
        return method_exists($this, $getter);
    }

    public function getAttribute(string $name)
    {
        $getter = 'get' . ucfirst($name);
        return method_exists($this, $getter) ? $this->$getter() : null;
    }

    public function setAttribute(string $name, $value)
    {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        }
    }

    public function __serialize(): array
    {
        return [
            'access_token' => base64_encode($this->access_token),
            'refresh_token' => base64_encode($this->refresh_token),
            'expired_at' => $this->expired_at,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->access_token = isset($data['access_token']) ? base64_decode($data['access_token']) : $this->access_token;
        $this->refresh_token = isset($data['refresh_token']) ?  base64_decode($data['refresh_token']) : $this->refresh_token;
        if (isset($data['expired_at'])) {
            if ($data['expired_at'] instanceof \DateTime) {
                $this->expired_at = $data['expired_at'];
            } elseif (is_string($data['expired_at'])) {
                $this->expired_at = new \DateTime($data['expired_at']);
            }
        }
    }

    public function serialize(): ?string
    {
        if ($this->access_token === null) {
            return null;
        }
        return serialize($this->__serialize());
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized): void
    {
        $this->__unserialize(unserialize($serialized));
    }
}