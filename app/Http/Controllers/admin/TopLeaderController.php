<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TopLeaderRequest;
use App\Services\TopLeaderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TopLeaderController extends Controller
{
    public function __construct(
        protected TopLeaderService $service
    ) {}

    public function index(): View
    {
        $topLeaders = $this->service->getAllPaginated();
        $posisiOptions = $this->service->getPositions();

        return view('admin.top-leader.index', compact('topLeaders', 'posisiOptions'));
    }

    public function store(TopLeaderRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('top-leader.index')->with('success', 'Top Leader berhasil ditambahkan.');
    }

    public function update(TopLeaderRequest $request, int $id): RedirectResponse
    {
        $topLeader = $this->service->findById($id);
        $this->service->update($topLeader, $request->validated());

        return redirect()->route('top-leader.index')->with('success', 'Top Leader berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $topLeader = $this->service->findById($id);
        $this->service->delete($topLeader);

        return redirect()->route('top-leader.index')->with('success', 'Top Leader berhasil dihapus.');
    }
}