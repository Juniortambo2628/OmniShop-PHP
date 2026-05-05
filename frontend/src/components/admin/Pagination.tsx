'use client';

import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';

interface PaginationProps {
  currentPage: number;
  lastPage: number;
  total: number;
  onPageChange: (page: number) => void;
  perPage?: number;
}

export default function Pagination({ currentPage, lastPage, total, onPageChange, perPage = 20 }: PaginationProps) {
  if (lastPage <= 1) return null;

  const startRange = (currentPage - 1) * perPage + 1;
  const endRange = Math.min(currentPage * perPage, total);

  // Generate page numbers to show
  const getPageNumbers = () => {
    const delta = 2;
    const range = [];
    for (let i = Math.max(2, currentPage - delta); i <= Math.min(lastPage - 1, currentPage + delta); i++) {
      range.push(i);
    }

    if (currentPage - delta > 2) {
      range.unshift('...');
    }
    if (currentPage + delta < lastPage - 1) {
      range.push('...');
    }

    range.unshift(1);
    if (lastPage !== 1) {
      range.push(lastPage);
    }

    return range;
  };

  return (
    <div className="flex flex-col sm:flex-row items-center justify-between gap-4 py-6 px-2">
      <div className="text-xs font-bold text-gray-400 uppercase tracking-widest">
        Showing <span className="text-gray-900">{startRange}</span> to <span className="text-gray-900">{endRange}</span> of <span className="text-gray-900">{total}</span> results
      </div>

      <div className="flex items-center gap-1.5">
        <button
          onClick={() => onPageChange(1)}
          disabled={currentPage === 1}
          className="p-2 rounded-xl hover:bg-gray-100 disabled:opacity-30 disabled:hover:bg-transparent transition-all text-gray-600"
        >
          <ChevronsLeft size={18} />
        </button>
        <button
          onClick={() => onPageChange(currentPage - 1)}
          disabled={currentPage === 1}
          className="p-2 rounded-xl hover:bg-gray-100 disabled:opacity-30 disabled:hover:bg-transparent transition-all text-gray-600"
        >
          <ChevronLeft size={18} />
        </button>

        <div className="flex items-center gap-1 mx-2">
          {getPageNumbers().map((page, i) => (
            <button
              key={i}
              onClick={() => typeof page === 'number' && onPageChange(page)}
              disabled={page === '...' || page === currentPage}
              className={`min-w-[40px] h-10 rounded-xl text-sm font-bold transition-all ${
                page === currentPage
                  ? 'bg-teal-600 text-white shadow-lg shadow-teal-600/20'
                  : page === '...'
                  ? 'cursor-default text-gray-400'
                  : 'text-gray-600 hover:bg-gray-100'
              }`}
            >
              {page}
            </button>
          ))}
        </div>

        <button
          onClick={() => onPageChange(currentPage + 1)}
          disabled={currentPage === lastPage}
          className="p-2 rounded-xl hover:bg-gray-100 disabled:opacity-30 disabled:hover:bg-transparent transition-all text-gray-600"
        >
          <ChevronRight size={18} />
        </button>
        <button
          onClick={() => onPageChange(lastPage)}
          disabled={currentPage === lastPage}
          className="p-2 rounded-xl hover:bg-gray-100 disabled:opacity-30 disabled:hover:bg-transparent transition-all text-gray-600"
        >
          <ChevronsRight size={18} />
        </button>
      </div>
    </div>
  );
}
