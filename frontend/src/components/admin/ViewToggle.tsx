'use client';

interface ViewToggleProps {
  view: 'grid' | 'list';
  onChange: (view: 'grid' | 'list') => void;
  storageKey?: string;
}

export default function ViewToggle({ view, onChange, storageKey }: ViewToggleProps) {
  const handleChange = (newView: 'grid' | 'list') => {
    onChange(newView);
    if (storageKey) {
      localStorage.setItem(storageKey, newView);
    }
  };

  return (
    <div className="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5">
      <button
        onClick={() => handleChange('grid')}
        className={`px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${
          view === 'grid'
            ? 'bg-teal-600 text-white shadow-sm'
            : 'text-gray-500 hover:text-gray-700'
        }`}
        title="Grid View"
      >
        <span className="flex items-center gap-1.5">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" strokeWidth="1.5">
            <rect x="1" y="1" width="5" height="5" rx="1" />
            <rect x="8" y="1" width="5" height="5" rx="1" />
            <rect x="1" y="8" width="5" height="5" rx="1" />
            <rect x="8" y="8" width="5" height="5" rx="1" />
          </svg>
          Grid
        </span>
      </button>
      <button
        onClick={() => handleChange('list')}
        className={`px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${
          view === 'list'
            ? 'bg-teal-600 text-white shadow-sm'
            : 'text-gray-500 hover:text-gray-700'
        }`}
        title="List View"
      >
        <span className="flex items-center gap-1.5">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" strokeWidth="1.5">
            <line x1="1" y1="3" x2="13" y2="3" />
            <line x1="1" y1="7" x2="13" y2="7" />
            <line x1="1" y1="11" x2="13" y2="11" />
          </svg>
          List
        </span>
      </button>
    </div>
  );
}
