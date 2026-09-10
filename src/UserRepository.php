<?php

declare(strict_types=1);

final class UserRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<array{id: int, nome: string, email: string, admin: bool}> */
    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, nome, email, admin FROM users ORDER BY nome ASC'
        );

        $users = [];

        foreach ($stmt->fetchAll() as $row) {
            $users[] = $this->normalize($row);
        }

        return $users;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email, admin FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!is_array($row)) {
            return null;
        }

        return $this->normalize($row);
    }

    /** @param array<string, mixed> $input */
    public function save(array $input, ?int $id = null): array
    {
        $nome = trim((string) ($input['nome'] ?? ''));
        $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $admin = filter_var($input['admin'] ?? false, FILTER_VALIDATE_BOOL);

        $this->assertValid($nome, $email, $password, $id);
        $this->assertUniqueEmail($email, $id);

        if ($id === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (nome, email, password, admin)
                 VALUES (:nome, :email, :password, :admin)'
            );
            $stmt->execute([
                'nome' => $nome,
                'email' => $email,
                'password' => $this->hashPassword($password),
                'admin' => $admin ? 1 : 0,
            ]);

            return $this->find((int) $this->pdo->lastInsertId())
                ?? throw new RuntimeException('Não foi possível cadastrar o usuário.');
        }

        $existing = $this->find($id);

        if ($existing === null) {
            throw new InvalidArgumentException('Usuário não encontrado.');
        }

        if ($password !== '') {
            $stmt = $this->pdo->prepare(
                'UPDATE users
                 SET nome = :nome, email = :email, password = :password, admin = :admin
                 WHERE id = :id'
            );
            $stmt->execute([
                'nome' => $nome,
                'email' => $email,
                'password' => $this->hashPassword($password),
                'admin' => $admin ? 1 : 0,
                'id' => $id,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE users
                 SET nome = :nome, email = :email, admin = :admin
                 WHERE id = :id'
            );
            $stmt->execute([
                'nome' => $nome,
                'email' => $email,
                'admin' => $admin ? 1 : 0,
                'id' => $id,
            ]);
        }

        return $this->find($id)
            ?? throw new RuntimeException('Não foi possível atualizar o usuário.');
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function authenticate(string $email, string $password): ?array
    {
        $email = mb_strtolower(trim($email));

        if ($email === '' || $password === '') {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email, password, admin FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        if (!is_array($row)) {
            return null;
        }

        $hash = (string) ($row['password'] ?? '');

        if ($hash === '' || !password_verify($password, $hash)) {
            return null;
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $rehash = password_hash($password, PASSWORD_DEFAULT);

            if ($rehash !== false) {
                $update = $this->pdo->prepare(
                    'UPDATE users SET password = :password WHERE id = :id'
                );
                $update->execute([
                    'password' => $rehash,
                    'id' => (int) $row['id'],
                ]);
            }
        }

        return $this->normalize($row);
    }

    private function hashPassword(string $password): string
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($hash === false) {
            throw new RuntimeException('Não foi possível gerar o hash da senha.');
        }

        return $hash;
    }

    private function assertValid(string $nome, string $email, string $password, ?int $id): void
    {
        if ($nome === '') {
            throw new InvalidArgumentException('Informe o nome do usuário.');
        }

        if (mb_strlen($nome) > 80) {
            throw new InvalidArgumentException('O nome deve ter no máximo 80 caracteres.');
        }

        if ($email === '') {
            throw new InvalidArgumentException('Informe o e-mail do usuário.');
        }

        if (mb_strlen($email) > 191) {
            throw new InvalidArgumentException('O e-mail deve ter no máximo 191 caracteres.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Informe um e-mail válido.');
        }

        if ($id === null && $password === '') {
            throw new InvalidArgumentException('Informe a senha do usuário.');
        }

        if ($password !== '' && mb_strlen($password) < 6) {
            throw new InvalidArgumentException('A senha deve ter no mínimo 6 caracteres.');
        }
    }

    private function assertUniqueEmail(string $email, ?int $id): void
    {
        $sql = 'SELECT id FROM users WHERE email = :email';
        $params = ['email' => $email];

        if ($id !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $id;
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->fetch() !== false) {
            throw new InvalidArgumentException('Já existe um usuário com este e-mail.');
        }
    }

    /** @param array<string, mixed> $row */
    private function normalize(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'nome' => (string) ($row['nome'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'admin' => ((int) ($row['admin'] ?? 0)) === 1,
        ];
    }
}
