@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Move node aggregate without dimensions

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Node':
      properties:
        text:
          type: string

    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |

    And I am in workspace "live"
    And I am in dimension space point {}

    And I am user identified by "initiating-user-identifier"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId           | nodeName   | parentNodeAggregateId     | nodeTypeName                        | initialPropertyValues                                      |
      | sir-david-nodenborough    | node       | lady-eleonode-rootford    | Neos.ContentRepository.Testing:Node | {}                                                         |
      | nody-mc-nodeface          | child-node | sir-david-nodenborough    | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Nody Mc Nodeface"}          |
      | sir-nodeward-nodington-iv | bakura     | lady-eleonode-rootford    | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Nodeward Nodington IV"} |
      | lady-childton             | ketchup    | sir-nodeward-nodington-iv | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Lady Childton"}         |


    Then the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |
    And I am in workspace "user-workspace"
    And I am in dimension space point {}

  Scenario: Move nodeAggregate into new parent
    When the command MoveNodeAggregate is executed with payload:
      | Key                      | Value                       |
      | nodeAggregateId          | "nody-mc-nodeface"          |
      | newParentNodeAggregateId | "sir-nodeward-nodington-iv" |

    Then I expect the ChangeProjection to have the following changes in "user-cs-id":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {}                        |
    And I expect the ChangeProjection to have no changes in "cs-identifier"

  Scenario: Move nodeAggregate with children into new parent
    When the command MoveNodeAggregate is executed with payload:
      | Key                      | Value                       |
      | nodeAggregateId          | "sir-nodeward-nodington-iv" |
      | newParentNodeAggregateId | "nody-mc-nodeface"          |

    Then I expect the ChangeProjection to have the following changes in "user-cs-id":
      | nodeAggregateId           | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-nodeward-nodington-iv | 0       | 0       | 1     | 0       | {}                        |
    And I expect the ChangeProjection to have no changes in "cs-identifier"

