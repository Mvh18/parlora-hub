import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { useTrans } from '@/hooks/use-trans';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    const { t } = useTrans();

    return (
        <>
            <Head title={t('settings.appearance.head')} />

            <h1 className="sr-only">{t('settings.appearance.head')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('settings.appearance.head')}
                    description={t('settings.appearance.description')}
                />
                <AppearanceTabs />
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'settings.appearance.head',
            href: editAppearance(),
        },
    ],
};
