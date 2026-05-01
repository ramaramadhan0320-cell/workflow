<?php

namespace App\Models;

use CodeIgniter\Model;

class AnnouncementModel extends Model
{
    protected $table            = 'announcements';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'title', 'content', 'status'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getAnnouncementsWithUser($status = null)
    {
        $builder = $this->db->table($this->table)
            ->select('announcements.*, users.username, users.profile')
            ->join('users', 'users.id = announcements.user_id');
        
        if ($status) {
            $builder->where('announcements.status', $status);
        }
        
        return $builder->orderBy('announcements.created_at', 'DESC')->get()->getResultArray();
    }

    public function getVisibleAnnouncements($userId, $isAdmin = false)
    {
        $builder = $this->db->table($this->table)
            ->select('announcements.*, users.username, users.profile')
            ->join('users', 'users.id = announcements.user_id');

        if ($isAdmin) {
            // Admin sees all
            return $builder->orderBy('announcements.created_at', 'DESC')->get()->getResultArray();
        } else {
            // Users see approved ones OR their own pending/rejected ones
            return $builder->groupStart()
                ->where('announcements.status', 'approved')
                ->orWhere('announcements.user_id', $userId)
                ->groupEnd()
                ->orderBy('announcements.created_at', 'DESC')
                ->get()
                ->getResultArray();
        }
    }
}
