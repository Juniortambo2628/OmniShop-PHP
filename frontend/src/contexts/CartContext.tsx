'use client';

import { createContext, useContext, useState, useEffect, ReactNode } from 'react';

export interface CartItem {
  cart_id: string; // Unique ID for cart item (e.g. prod_id + color_id)
  product_id: string;
  name: string;
  price: number;
  quantity: number;
  color: string | null;
  image: string | null;
  category: string;
}

interface CartContextType {
  items: CartItem[];
  addToCart: (item: Omit<CartItem, 'cart_id'>) => void;
  removeFromCart: (cart_id: string) => void;
  updateQuantity: (cart_id: string, quantity: number) => void;
  clearCart: () => void;
  totalItems: number;
  subtotal: number;
}

const CartContext = createContext<CartContextType | undefined>(undefined);

export function CartProvider({ children, eventSlug }: { children: ReactNode, eventSlug: string }) {
  const [items, setItems] = useState<CartItem[]>([]);
  const storageKey = `omnishop_cart_${eventSlug}`;

  useEffect(() => {
    const saved = localStorage.getItem(storageKey);
    if (saved) {
      try { setItems(JSON.parse(saved)); } catch (e) {}
    }
  }, [storageKey]);

  useEffect(() => {
    localStorage.setItem(storageKey, JSON.stringify(items));
  }, [items, storageKey]);

  const addToCart = (newItem: Omit<CartItem, 'cart_id'>) => {
    const cart_id = `${newItem.product_id}-${newItem.color || 'default'}`;
    setItems(prev => {
      const existing = prev.find(i => i.cart_id === cart_id);
      if (existing) {
        return prev.map(i => i.cart_id === cart_id ? { ...i, quantity: i.quantity + newItem.quantity } : i);
      }
      return [...prev, { ...newItem, cart_id }];
    });
  };

  const removeFromCart = (cart_id: string) => {
    setItems(prev => prev.filter(i => i.cart_id !== cart_id));
  };

  const updateQuantity = (cart_id: string, quantity: number) => {
    if (quantity < 1) return removeFromCart(cart_id);
    setItems(prev => prev.map(i => i.cart_id === cart_id ? { ...i, quantity } : i));
  };

  const clearCart = () => setItems([]);

  const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);
  const subtotal = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);

  return (
    <CartContext.Provider value={{ items, addToCart, removeFromCart, updateQuantity, clearCart, totalItems, subtotal }}>
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  const ctx = useContext(CartContext);
  if (!ctx) throw new Error('useCart must be used within CartProvider');
  return ctx;
}
