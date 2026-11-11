import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

/**
 * The brand mark: the glyph on an inverse speech bubble. Decorative — the
 * product name always appears next to it or as the link's accessible name.
 */
export default function BrandMark({ className }: { className?: string }) {
    return (
        <span
            aria-hidden="true"
            className={cn(
                'flex size-9 shrink-0 items-center justify-center bubble-bl-xs bg-inverse text-on-inverse',
                className,
            )}
        >
            <AppLogoIcon className="size-5" />
        </span>
    );
}
