// frontend/src/types/index.ts

export type Role = 'buyer' | 'farmer' | 'cooperative' | 'admin';
export type AccountStatus = 'active' | 'banned';
export type ListingStatus = 'active' | 'pending' | 'removed';

export interface UserProfile {
  id: string;
  email: string;
  displayName: string;
  role: Role;
  status: AccountStatus;
  phone: string | null;
  orgName: string | null;
  avatarUrl: string | null;
  createdAt?: { _seconds: number } | string | null;
}

export interface Product {
  id: string;
  ownerId: string;
  ownerRole: Role;
  name: string;
  description: string;
  category: 'produce' | 'supply';
  subcategory: string;
  price: number;
  unit: string;
  stock: number;
  images: string[];
  tags: Array<'organic' | 'just-harvested' | 'member-price'>;
  status: ListingStatus;
}

export interface Equipment {
  id: string;
  ownerId: string;
  ownerRole: Role;
  name: string;
  description: string;
  category: string;
  listingType: 'sale' | 'rent';
  price: number;
  images: string[];
  status: ListingStatus;
}

export interface OrderItem {
  productId: string;
  name: string;
  unit: string;
  price: number;
  qty: number;
  lineTotal: number;
  sellerId: string;
}

export interface Order {
  id: string;
  buyerId: string;
  sellerIds: string[];
  items: OrderItem[];
  total: number;
  status: 'placed' | 'confirmed' | 'fulfilled' | 'cancelled';
  createdAt?: unknown;
}

export interface Rental {
  id: string;
  renterId: string;
  ownerId: string;
  equipmentId: string;
  equipmentName: string;
  startDate: unknown;
  endDate: unknown;
  days: number;
  dailyRate: number;
  totalCost: number;
  status: 'requested' | 'approved' | 'active' | 'returned' | 'cancelled';
}

export interface Report {
  id: string;
  reporterId: string;
  targetType: 'product' | 'equipment' | 'user';
  targetId: string;
  reason: string;
  details: string;
  status: 'open' | 'reviewed' | 'dismissed';
  resolutionNote?: string | null;
}

export interface CartLine {
  productId: string;
  name: string;
  unit: string;
  price: number;
  qty: number;
  stock: number;
}

export interface Paginated<T> {
  items: T[];
  nextCursor: string | null;
}
