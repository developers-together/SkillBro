<?php

namespace App\Enums;

enum LectureType: string
{
    case Video = 'video';
    case Text = 'text';
    case Quiz = 'quiz';
}
