export default function Heading({
    title,
    description,
    variant = 'default',
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
}) {
    return (
        <header className={variant === 'small' ? '' : 'mb-8 space-y-1'}>
            <h2
                className={
                    variant === 'small'
                        ? 'mb-1 text-h3 font-bold text-ink'
                        : 'text-h2 font-bold text-ink'
                }
            >
                {title}
            </h2>
            {description && (
                <p className="text-body-sm text-ink-muted">{description}</p>
            )}
        </header>
    );
}
