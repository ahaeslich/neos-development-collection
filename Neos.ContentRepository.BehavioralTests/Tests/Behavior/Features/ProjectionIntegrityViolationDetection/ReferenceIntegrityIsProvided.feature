@contentrepository @adapters=DoctrineDBAL
Feature: Run integrity violation detection regarding reference relations

  As a user of the CR I want to know whether there are disconnected reference relations

  Background:
    Given using the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | de, gsw, fr | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     |
      | workspaceName               | "live"                                    |
      | contentStreamId             | "cs-identifier"                           |
      | nodeAggregateId             | "source-nodandaise"                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint   | {"language":"de"}                         |
      | coveredDimensionSpacePoints | [{"language":"de"}]                       |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                  |
      | nodeAggregateClassification | "regular"                                 |

  Scenario: Reference a non-existing node aggregate
    When the event NodeReferencesWereSet was published with payload:
      | Key                                      | Value                                                              |
      | workspaceName                            | "live"                                                             |
      | contentStreamId                          | "cs-identifier"                                                    |
      | sourceNodeAggregateId                    | "source-nodandaise"                                                |
      | affectedSourceOriginDimensionSpacePoints | [{"language":"de"}]                                                |
      | references                               | [{"referenceName": "referenceProperty", "references": [{"target":"anthony-destinode", "properties":null}]}] |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597919585

  Scenario: Reference a node aggregate not covering any of the DSPs the source does
    When the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     |
      | workspaceName               | "live"                                    |
      | contentStreamId             | "cs-identifier"                           |
      | nodeAggregateId             | "anthony-destinode"                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint   | {"language":"fr"}                         |
      | coveredDimensionSpacePoints | [{"language":"fr"}]                       |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                  |
      | nodeAggregateClassification | "regular"                                 |
    And the event NodeReferencesWereSet was published with payload:
      | Key                                      | Value                                                              |
      | workspaceName                            | "live"                                                             |
      | contentStreamId                          | "cs-identifier"                                                    |
      | sourceNodeAggregateId                    | "source-nodandaise"                                                |
      | affectedSourceOriginDimensionSpacePoints | [{"language":"de"}]                                                |
      | references                               | [{"referenceName": "referenceProperty", "references": [{"target":"anthony-destinode", "properties":null}]}] |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597919585
