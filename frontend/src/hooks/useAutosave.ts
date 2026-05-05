'use client';

import { useState, useEffect, useRef, useCallback } from 'react';
import { apiFetch } from '@/lib/api';

interface UseAutosaveOptions<T> {
  type: string;
  id?: string;
  token?: string;
  data: T;
  onLoad?: (data: T) => void;
  debounceMs?: number;
}

export function useAutosave<T>({ type, id, token, data, onLoad, debounceMs = 2000 }: UseAutosaveOptions<T>) {
  const [isSaving, setIsSaving] = useState(false);
  const [lastSaved, setLastSaved] = useState<Date | null>(null);
  const [hasDraft, setHasDraft] = useState(false);
  const initialLoadDone = useRef(false);
  const timerRef = useRef<NodeJS.Timeout | null>(null);

  // Load draft on mount
  useEffect(() => {
    if (!token || initialLoadDone.current) return;

    const loadDraft = async () => {
      try {
        const params = new URLSearchParams({ type });
        if (id) params.set('id', id);
        
        const res = await apiFetch(`/drafts?${params.toString()}`, { token });
        if (res.draft) {
          setHasDraft(true);
          if (onLoad) onLoad(res.draft.content as T);
        }
      } catch (err) {
        console.error('Failed to load draft:', err);
      } finally {
        initialLoadDone.current = true;
      }
    };

    loadDraft();
  }, [token, type, id, onLoad]);

  // Save draft on data change
  useEffect(() => {
    if (!token || !initialLoadDone.current) return;

    if (timerRef.current) clearTimeout(timerRef.current);

    timerRef.current = setTimeout(async () => {
      setIsSaving(true);
      try {
        await apiFetch('/drafts', {
          method: 'POST',
          token,
          body: JSON.stringify({
            type,
            id,
            content: data
          })
        });
        setLastSaved(new Date());
        setHasDraft(true);
      } catch (err) {
        console.error('Failed to save draft:', err);
      } finally {
        setIsSaving(false);
      }
    }, debounceMs);

    return () => {
      if (timerRef.current) clearTimeout(timerRef.current);
    };
  }, [data, token, type, id, debounceMs]);

  const clearDraft = useCallback(async () => {
    if (!token) return;
    try {
      const params = new URLSearchParams({ type });
      if (id) params.set('id', id);
      await apiFetch(`/drafts?${params.toString()}`, {
        method: 'DELETE',
        token
      });
      setHasDraft(false);
    } catch (err) {
      console.error('Failed to clear draft:', err);
    }
  }, [token, type, id]);

  return { isSaving, lastSaved, hasDraft, clearDraft };
}
