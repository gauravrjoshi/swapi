<?php

namespace App\Services;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;

class UserService
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Get user profile.
     *
     * @param int $id
     * @return User|null
     */
    public function getUserProfile(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    // Get all users
    public function getAllUsers()
    {
        return $this->userRepository->getAll();
    }

    /**
     * Update user profile.
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function updateProfile(User $user, array $data): User
    {
        return $this->userRepository->update($user, $data);
    }
}
