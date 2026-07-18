<?php

namespace App\Core;

class Paginator {
    private int $totalItems;
    private int $limit;
    private int $currentPage;
    private int $totalPages;

    public function __construct(int $totalItems, int $limit = 10, int $currentPage = 1) {
        $this->totalItems = $totalItems;
        $this->limit = max(1, $limit);
        $this->totalPages = (int)ceil($totalItems / $this->limit);
        $this->currentPage = min(max(1, $currentPage), max(1, $this->totalPages));
    }

    public function getLimit(): int {
        return $this->limit;
    }

    public function getOffset(): int {
        return ($this->currentPage - 1) * $this->limit;
    }

    public function getCurrentPage(): int {
        return $this->currentPage;
    }

    public function getTotalPages(): int {
        return $this->totalPages;
    }

    public function getTotalItems(): int {
        return $this->totalItems;
    }

    public function hasPrev(): bool {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool {
        return $this->currentPage < $this->totalPages;
    }

    public function getPrevPage(): int {
        return $this->currentPage - 1;
    }

    public function getNextPage(): int {
        return $this->currentPage + 1;
    }

    /**
     * Render the pagination block.
     */
    public function render(string $baseUrl, array $queryParams = []): string {
        if ($this->totalPages <= 1) {
            return '';
        }

        $html = '<div class="flex items-center justify-between border-t border-slate-200 bg-white px-4 py-3 sm:px-6 mt-4 rounded-lg shadow-sm">';
        $html .= '<div class="flex flex-1 justify-between sm:hidden">';
        
        if ($this->hasPrev()) {
            $prevParams = array_merge($queryParams, ['page' => $this->getPrevPage()]);
            $prevUrl = $baseUrl . '?' . http_build_query($prevParams);
            $html .= '<a href="' . $prevUrl . '" class="relative inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Previous</a>';
        } else {
            $html .= '<span class="relative inline-flex items-center rounded-md border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-400 cursor-not-allowed">Previous</span>';
        }

        if ($this->hasNext()) {
            $nextParams = array_merge($queryParams, ['page' => $this->getNextPage()]);
            $nextUrl = $baseUrl . '?' . http_build_query($nextParams);
            $html .= '<a href="' . $nextUrl . '" class="relative ml-3 inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Next</a>';
        } else {
            $html .= '<span class="relative ml-3 inline-flex items-center rounded-md border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-400 cursor-not-allowed">Next</span>';
        }

        $html .= '</div>';
        $html .= '<div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">';
        $html .= '<div>';
        $startItem = $this->getOffset() + 1;
        $endItem = min($this->getOffset() + $this->limit, $this->totalItems);
        $html .= '<p class="text-sm text-slate-700">Showing <span class="font-medium">' . $startItem . '</span> to <span class="font-medium">' . $endItem . '</span> of <span class="font-medium">' . $this->totalItems . '</span> results</p>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">';
        
        if ($this->hasPrev()) {
            $prevParams = array_merge($queryParams, ['page' => $this->getPrevPage()]);
            $prevUrl = $baseUrl . '?' . http_build_query($prevParams);
            $html .= '<a href="' . $prevUrl . '" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 focus:outline-offset-0"><span class="sr-only">Previous</span><i class="fas fa-chevron-left text-xs"></i></a>';
        } else {
            $html .= '<span class="relative inline-flex items-center rounded-l-md px-2 py-2 text-slate-300 ring-1 ring-inset ring-slate-200 bg-slate-50 cursor-not-allowed"><i class="fas fa-chevron-left text-xs"></i></span>';
        }

        for ($i = 1; $i <= $this->totalPages; $i++) {
            $params = array_merge($queryParams, ['page' => $i]);
            $url = $baseUrl . '?' . http_build_query($params);
            
            if ($i === $this->currentPage) {
                $html .= '<span aria-current="page" class="relative z-10 inline-flex items-center bg-orange-500 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">' . $i . '</span>';
            } else {
                $html .= '<a href="' . $url . '" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 focus:outline-offset-0">' . $i . '</a>';
            }
        }

        if ($this->hasNext()) {
            $nextParams = array_merge($queryParams, ['page' => $this->getNextPage()]);
            $nextUrl = $baseUrl . '?' . http_build_query($nextParams);
            $html .= '<a href="' . $nextUrl . '" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 focus:outline-offset-0"><span class="sr-only">Next</span><i class="fas fa-chevron-right text-xs"></i></a>';
        } else {
            $html .= '<span class="relative inline-flex items-center rounded-r-md px-2 py-2 text-slate-300 ring-1 ring-inset ring-slate-200 bg-slate-50 cursor-not-allowed"><i class="fas fa-chevron-right text-xs"></i></span>';
        }

        $html .= '</nav>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
