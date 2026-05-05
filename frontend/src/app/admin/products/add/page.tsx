'use client';

import { useSearchParams } from 'next/navigation';
import ProductForm from '@/components/admin/ProductForm';

export default function AddProductPage() {
  const searchParams = useSearchParams();
  const fromCatalogId = searchParams.get('from_catalog') || undefined;

  return <ProductForm fromCatalogId={fromCatalogId} />;
}
