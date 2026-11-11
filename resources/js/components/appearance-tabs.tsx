import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { HTMLAttributes } from 'react';
import type { Appearance } from '@/hooks/use-appearance';
import { useTrans } from '@/hooks/use-trans';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();
    const { t } = useTrans();

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: t('settings.appearance.light') },
        { value: 'dark', icon: Moon, label: t('settings.appearance.dark') },
        {
            value: 'system',
            icon: Monitor,
            label: t('settings.appearance.system'),
        },
    ];

    return (
        <div
            className={cn(
                'inline-flex gap-1 rounded-button border border-border bg-surface p-1',
                className,
            )}
            {...props}
        >
            {tabs.map(({ value, icon: Icon, label }) => (
                <button
                    key={value}
                    onClick={() => updateAppearance(value)}
                    className={cn(
                        'flex min-h-11 items-center rounded-control px-3.5 transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                        appearance === value
                            ? 'bg-accent-soft font-semibold text-primary'
                            : 'text-ink-secondary hover:bg-canvas hover:text-ink',
                    )}
                >
                    <Icon className="-ml-1 h-4 w-4" />
                    <span className="ml-1.5 text-sm">{label}</span>
                </button>
            ))}
        </div>
    );
}
