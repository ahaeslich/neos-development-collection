<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Auth\Privilege;
use Neos\ContentRepository\Core\SharedModel\Auth\UserId;
use Neos\ContentRepository\Core\SharedModel\Auth\AuthProviderInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

final class FakeAuthProvider implements AuthProviderInterface
{
    public static ?UserId $userId = null;

    public static function setUserId(UserId $userId): void
    {
        self::$userId = $userId;
    }

    public function getAuthenticatedUserId(): ?UserId
    {
        return self::$userId ?? null;
    }

    public function getVisibilityConstraints(WorkspaceName $workspaceName): VisibilityConstraints
    {
        return VisibilityConstraints::withoutRestrictions();
    }

    public function getReadNodesFromWorkspacePrivilege(WorkspaceName $workspaceName): Privilege
    {
        return Privilege::granted(self::class . ' always grants privileges');
    }

    public function getCommandPrivilege(CommandInterface $command): Privilege
    {
        return Privilege::granted(self::class . ' always grants privileges');
    }
}
