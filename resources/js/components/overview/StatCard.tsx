import type { ReactNode } from 'react';
import styles from './stat-card.module.css';

interface StatCardProps {
    label: string;
    value: string;
    context: string;
    icon: ReactNode;
    tone?: 'default' | 'positive' | 'warning';
}

export function StatCard({ label, value, context, icon, tone = 'default' }: StatCardProps) {
    return (
        <article className={styles.card} data-tone={tone}>
            <div className={styles.top}>
                <span className={styles.label}>{label}</span>
                <span className={styles.icon} aria-hidden="true">
                    {icon}
                </span>
            </div>
            <p className={styles.value}>{value}</p>
            <p className={styles.context}>{context}</p>
        </article>
    );
}
