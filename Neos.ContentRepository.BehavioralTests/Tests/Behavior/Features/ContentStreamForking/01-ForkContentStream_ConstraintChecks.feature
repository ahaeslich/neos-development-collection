@contentrepository @adapters=DoctrineDBAL
Feature: ForkContentStream Without Dimensions

  We have only one node underneath the root node: /foo.
  LIVE Content Stream ID: cs-identifier
  We fork the live content stream as ID user-cs-identifier
  and then we commit a modification in the LIVE content stream.
  We then expect the *forked* content stream to contain the *original* value; and the *live* content stream must contain the changed value.

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                    |
      | workspaceName               | "live"                                   |
      | contentStreamId             | "cs-identifier"                          |
      | nodeAggregateId             | "nody-mc-nodeface"                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint   | {}                                       |
      | coveredDimensionSpacePoints | [{}]                                     |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                 |
      | nodeName                    | "child"                                  |
      | nodeAggregateClassification | "regular"                                |
    And the event NodePropertiesWereSet was published with payload:
      | Key                          | Value                                                   |
      | workspaceName                | "live"                                                  |
      | contentStreamId              | "cs-identifier"                                         |
      | nodeAggregateId              | "nody-mc-nodeface"                                      |
      | originDimensionSpacePoint    | {}                                                      |
      | affectedDimensionSpacePoints | [{}]                                                    |
      | propertyValues               | {"text": {"value": "original value", "type": "string"}} |
      | propertiesToUnset            | {}                                                      |

  Scenario: Try to create a workspace with the base workspace referring to a closed content stream
    When the event ContentStreamWasClosed was published with payload:
      | Key             | Value           |
      | contentStreamId | "cs-identifier" |
    When the command CreateWorkspace is executed with payload and exceptions are caught:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    Then the last command should have thrown an exception of type "ContentStreamIsClosed"
