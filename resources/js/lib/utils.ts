import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function useMoney() {
  const formatKES = (value) => {
    if (!value) return "0";
    const num = parseFloat(value.toString().replace(/[^\d.-]/g, ""));
    return new Intl.NumberFormat("en-KE", {
      style: "decimal", 
      minimumFractionDigits: 0,
      maximumFractionDigits: 2
    }).format(num);
  };

  return { formatKES };
}