import type { ReactNode } from 'react';
import styles from './panel.module.css';

interface PanelProps {
    title: string;
    description?: string;
    action?: ReactNode;
    children: ReactNode;
    className?: string;
    bodyClassName?: string;
}

export function Panel({ title, description, action, children, className, bodyClassName }: PanelProps) {
    return (
        <section className={[styles.panel, className].filter(Boolean).join(' ')}>
            <header className={styles.header}>
                <div className={styles.heading}>
                    <h2 className={styles.title}>{title}</h2>
                    {description ? <p className={styles.description}>{description}</p> : null}
                </div>
                {action ? <div className={styles.action}>{action}</div> : null}
            </header>
            <div className={[styles.body, bodyClassName].filter(Boolean).join(' ')}>{children}</div>
        </section>
    );
}
