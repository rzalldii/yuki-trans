<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\MasterDataService;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function __construct(
        protected MasterDataService $masterDataService
    ) {}

    public function index(): View
    {
        return view('pages.finance.master-data', $this->masterDataService->getIndexData());
    }
}