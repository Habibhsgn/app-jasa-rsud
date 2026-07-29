<?php

namespace App\Services;

use App\Models\TopLeader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TopLeaderService
{
    protected array $positions = [
        'Direktur' => 'Direktur',
        'Kabid' => 'Kabid',
        'Kasie' => 'Kasie',
        'Bendahara' => 'Bendahara',
        'Casemix' => 'Casemix',
        'Costing' => 'Costing',
    ];

    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return TopLeader::orderBy('posisi')->orderBy('nama')->paginate($perPage);
    }

    public function getPositions(): array
    {
        return $this->positions;
    }

    public function create(array $data): TopLeader
    {
        return TopLeader::create($data);
    }

    public function update(TopLeader $topLeader, array $data): bool
    {
        return $topLeader->update($data);
    }

    public function delete(TopLeader $topLeader): bool
    {
        return $topLeader->delete();
    }

    public function findById(int $id): ?TopLeader
    {
        return TopLeader::find($id);
    }
}