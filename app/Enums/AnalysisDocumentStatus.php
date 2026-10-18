<?php

namespace App\Enums;

enum AnalysisDocumentStatus: string
{
    case Pending = 'pending';
    case Read = 'read';
    case Empty = 'empty';
    case Failed = 'failed';
}
