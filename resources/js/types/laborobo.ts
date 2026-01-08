// Organization types
export interface Organization {
    id: number;
    name: string;
    slug: string;
    logo?: string;
    user_id: number;
    created_at: string;
    updated_at: string;
}

// Work entity types (placeholder for future milestones)
export type EntityType = 'party' | 'project' | 'work_order' | 'task';

export interface Party {
    id: number;
    type: 'person' | 'organization';
    name: string;
    email?: string;
    phone?: string;
    organization_id: number;
    created_at: string;
    updated_at: string;
}

export interface Project {
    id: number;
    name: string;
    description?: string;
    organization_id: number;
    party_id?: number;
    status: 'active' | 'on_hold' | 'completed' | 'cancelled';
    start_date?: string;
    target_end_date?: string;
    created_at: string;
    updated_at: string;
}

export interface WorkOrder {
    id: number;
    name: string;
    description?: string;
    project_id: number;
    organization_id: number;
    status: 'draft' | 'approved' | 'in_progress' | 'blocked' | 'review' | 'completed';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    assignee_id?: number;
    due_date?: string;
    created_at: string;
    updated_at: string;
}

export interface Task {
    id: number;
    title: string;
    description?: string;
    work_order_id?: number;
    organization_id: number;
    status: 'todo' | 'in_progress' | 'blocked' | 'review' | 'done';
    assignee_id?: number;
    due_date?: string;
    created_at: string;
    updated_at: string;
}
