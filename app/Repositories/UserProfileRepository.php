<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\UserProfileRepositoryInterface;

final class UserProfileRepository extends BaseRepository implements UserProfileRepositoryInterface
{
    protected string $table = 'user_profiles';

    public function findByUserId(int $userId): ?array
    {
        if ($userId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT p.*,
                    u.email AS user_email,
                    u.full_name AS user_full_name,
                    u.phone AS user_phone,
                    u.role AS user_role,
                    nc.name AS nationality_name,
                    cc.name AS current_country_name,
                    pc.name AS preferred_country_name,
                    ci.name AS current_city_name
             FROM `user_profiles` p
             INNER JOIN `users` u ON u.id = p.user_id
             LEFT JOIN `countries` nc ON nc.id = p.nationality_country_id
             LEFT JOIN `countries` cc ON cc.id = p.current_country_id
             LEFT JOIN `countries` pc ON pc.id = p.preferred_country_id
             LEFT JOIN `cities` ci ON ci.id = p.current_city_id
             WHERE p.user_id = :user_id
             LIMIT 1',
            ['user_id' => $userId]
        );
    }

    public function upsertForUser(int $userId, array $data): void
    {
        $existing = $this->fetchOne(
            'SELECT `id` FROM `user_profiles` WHERE `user_id` = :user_id LIMIT 1',
            ['user_id' => $userId]
        );

        $fields = [
            'first_name', 'last_name', 'nic_passport', 'headline', 'summary',
            'date_of_birth', 'gender', 'marital_status', 'expected_salary',
            'nationality_country_id', 'current_country_id', 'preferred_country_id',
            'current_city_id', 'address', 'whatsapp', 'linkedin_url', 'visibility',
        ];

        $payload = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if ($existing === null) {
            $columns = array_merge(['user_id'], array_keys($payload));
            $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);
            $payload['user_id'] = $userId;
            $sql = sprintf(
                'INSERT INTO `user_profiles` (`%s`) VALUES (%s)',
                implode('`, `', $columns),
                implode(', ', $placeholders)
            );
            $this->query($sql, $payload);

            return;
        }

        if ($payload === []) {
            return;
        }

        $sets = [];
        foreach (array_keys($payload) as $column) {
            $sets[] = '`' . $column . '` = :' . $column;
        }
        $payload['user_id'] = $userId;
        $this->query(
            'UPDATE `user_profiles` SET ' . implode(', ', $sets) . ' WHERE `user_id` = :user_id',
            $payload
        );
    }

    public function updateAvatar(int $userId, ?string $path): void
    {
        $this->upsertForUser($userId, []);
        $this->query(
            'UPDATE `user_profiles` SET `avatar_path` = :path WHERE `user_id` = :user_id',
            ['path' => $path, 'user_id' => $userId]
        );
    }
}
