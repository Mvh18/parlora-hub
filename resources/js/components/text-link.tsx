import { Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type Props = ComponentProps<typeof Link>;

export default function TextLink({
    className = '',
    children,
    ...props
}: Props) {
    return (
        <Link
            className={cn(
                'font-medium text-primary underline decoration-accent-muted underline-offset-4 transition-colors duration-300 ease-out hover:text-accent-strong hover:decoration-current!',
                className,
            )}
            {...props}
        >
            {children}
        </Link>
    );
}
