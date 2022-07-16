<?php

namespace Yeast\Http;

enum MountType: string {
    case CONTROLLER = 'controller';
    case HANDLER = 'handler';
    case FILES = 'files';
}