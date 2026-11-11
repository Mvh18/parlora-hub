import { Link } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-8 bg-canvas p-6 md:p-10">
            <Link
                href={home()}
                className="flex min-h-11 items-center gap-2.5 rounded-control px-2 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <AppLogo />
            </Link>

            <main className="w-full max-w-md rounded-card border border-border bg-surface p-8 sm:p-10">
                <div className="flex flex-col gap-8">
                    <div className="space-y-2">
                        <h1 className="text-h1 font-bold text-ink">{title}</h1>
                        <p className="text-body-sm text-ink-muted">
                            {description}
                        </p>
                    </div>
                    {children}
                </div>
            </main>
        </div>
    );
}
