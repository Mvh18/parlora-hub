import BrandMark from '@/components/brand-mark';
import { useTrans } from '@/hooks/use-trans';

/** The brand lockup: mark, "Parlora" and the product name below it. */
export default function AppLogo() {
    const { t } = useTrans();

    return (
        <>
            <BrandMark className="size-8" />
            <div className="ml-1 flex flex-1 flex-col text-left">
                <span className="truncate font-display text-h3 leading-none font-bold text-ink">
                    {t('app.brand.name')}
                </span>
                <span className="mt-0.5 truncate text-caption text-ink-muted">
                    {t('app.brand.product')}
                </span>
            </div>
        </>
    );
}
