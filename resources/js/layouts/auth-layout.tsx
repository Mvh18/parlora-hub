import { useTrans } from '@/hooks/use-trans';
import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';

/**
 * `title` and `description` are translation keys, set by each page through
 * its static `layout` object or `setLayoutProps()`.
 */
export default function AuthLayout({
    title = '',
    description = '',
    children,
}: {
    title?: string;
    description?: string;
    children: React.ReactNode;
}) {
    const { t } = useTrans();

    return (
        <AuthLayoutTemplate
            title={title && t(title)}
            description={description && t(description)}
        >
            {children}
        </AuthLayoutTemplate>
    );
}
