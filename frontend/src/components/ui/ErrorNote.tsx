// frontend/src/components/ui/ErrorNote.tsx
import { AlertCircle } from 'lucide-react';

export default function ErrorNote({ message }: { message: string }) {
  if (!message) return null;
  return (
    <p
      role="alert"
      className="flex items-start gap-2 rounded-2xl bg-[#FBE9E4] px-4 py-3 text-sm text-[#8C3A22]"
    >
      <AlertCircle size={17} className="mt-0.5 shrink-0" />
      <span>{message}</span>
    </p>
  );
}
