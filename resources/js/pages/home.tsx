import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTrans } from '@/hooks/use-trans';
import { firstName, greetingKey } from '@/lib/greeting';

export default function Home() {
    const { auth } = usePage().props;
    const { t } = useTrans();
    // Read the clock once: the greeting stays put while the page is open.
    const [greeting] = useState(() => greetingKey(new Date()));

    return (
        <>
            <Head title={t('home.head')} />
            <div className="flex flex-1 flex-col gap-8 px-[clamp(20px,4vw,48px)] py-9">
                <h1 className="font-display text-display font-bold text-ink">
                    {t(greeting, {
                        name: firstName(auth.user.name),
                    })}
                </h1>

                <section
                    aria-labelledby="home-empty-title"
                    className="max-w-xl bubble-bl border border-border bg-surface p-8"
                >
                    <h2
                        id="home-empty-title"
                        className="font-display text-h2 font-bold text-ink"
                    >
                        {t('home.empty.title')}
                    </h2>
                    <p className="mt-2 text-body text-ink-secondary">
                        {t('home.empty.body')}
                    </p>
                </section>
            </div>
        </>
    );
}
