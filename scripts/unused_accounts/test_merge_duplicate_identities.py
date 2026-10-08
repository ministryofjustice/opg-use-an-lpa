import json
from pathlib import Path

import merge_duplicate_identities as mdi


def make_user(user_id, identity, last_login):
    return {
        "Id": user_id,
        "Identity": identity,
        "LastLogin": last_login,
    }


def make_mapping(mapping_id, user_id, actor_id, lpa_uid=None, sirius_uid=None):
    row = {
        "Id": mapping_id,
        "UserId": user_id,
        "ActorId": actor_id,
    }
    if lpa_uid is not None:
        row["LpaUid"] = lpa_uid
    if sirius_uid is not None:
        row["SiriusUid"] = sirius_uid
    return row


def test_determine_primary_picks_latest_lastlogin():
    users = [
        make_user("user-old", "urn1", "2026-03-10T08:49:11.883262"),
        make_user("user-new", "urn1", "2026-03-10T08:49:11.883265"),
    ]

    primary = mdi.determine_primary(users)

    assert primary["Id"] == "user-new"


def test_group_mappings_by_logical_key_groups_same_actor_and_lpa():
    mappings = [
        make_mapping("m1", "u1", 9, sirius_uid="700000000047"),
        make_mapping("m2", "u2", 9, sirius_uid="700000000047"),
        make_mapping("m3", "u2", 23, sirius_uid="700000000047"),
    ]

    grouped = mdi.group_mappings_by_logical_key(mappings)

    assert len(grouped) == 2
    assert len(grouped[(9, "700000000047")]) == 2
    assert len(grouped[(23, "700000000047")]) == 1


def test_choose_canonical_keeps_single_mapping_when_no_duplicates(monkeypatch):
    mappings = [
        make_mapping("m1", "primary", 9, sirius_uid="700000000047"),
    ]
    grouped = mdi.group_mappings_by_logical_key(mappings)

    monkeypatch.setattr(
        mdi,
        "get_viewer_codes_for_mapping",
        lambda _viewer_table, _mapping_id: [],
    )

    canonical_ids, delete_ids, viewer_updates = mdi.choose_canonical_mappings(
        grouped,
        viewer_table=object(),
        primary_user_id="primary",
    )

    assert canonical_ids == {"m1"}
    assert delete_ids == set()
    assert viewer_updates == []


def test_build_merge_plan_merge_only_case(monkeypatch):
    users = [
        make_user("primary", "urn1", "2026-03-10T08:49:11.883265"),
        make_user("secondary", "urn1", "2026-03-10T08:49:11.883262"),
    ]

    mappings_by_user = {
        "primary": [
            make_mapping("m-primary", "primary", 59, sirius_uid="700000000138"),
        ],
        "secondary": [
            make_mapping("m-secondary", "secondary", 23, sirius_uid="700000000138"),
        ],
    }

    monkeypatch.setattr(
        mdi,
        "get_user_lpas",
        lambda _lpa_table, user_id: mappings_by_user[user_id],
    )
    monkeypatch.setattr(
        mdi,
        "get_viewer_codes_for_mapping",
        lambda _viewer_table, _mapping_id: [],
    )

    plan = mdi.build_merge_plan_for_identity(
        identity="urn1",
        group=users,
        viewer_table=object(),
        lpa_table=object(),
    )

    assert plan["primary_user_id"] == "primary"
    assert plan["secondary_user_ids"] == ["secondary"]
    assert plan["move_mapping_ids"] == ["m-secondary"]
    assert plan["delete_mapping_ids"] == []
    assert plan["viewer_code_updates"] == []
    assert plan["delete_secondary_user_ids"] == ["secondary"]


def test_build_merge_plan_duplicate_mapping_with_viewer_codes_repoints_and_deletes(monkeypatch):
    users = [
        make_user("primary", "urn1", "2026-03-10T08:49:11.883265"),
        make_user("secondary", "urn1", "2026-03-10T08:49:11.883262"),
    ]

    mappings_by_user = {
        "primary": [
            make_mapping("primary-map", "primary", 9, sirius_uid="700000000047"),
        ],
        "secondary": [
            make_mapping("secondary-map", "secondary", 9, sirius_uid="700000000047"),
        ],
    }

    fake_codes = {
        "primary-map": [{"ViewerCode": "VC1", "UserLpaActor": "primary-map"}],
        "secondary-map": [{"ViewerCode": "VC2", "UserLpaActor": "secondary-map"}],
    }

    monkeypatch.setattr(
        mdi,
        "get_user_lpas",
        lambda _lpa_table, user_id: mappings_by_user[user_id],
    )
    monkeypatch.setattr(
        mdi,
        "get_viewer_codes_for_mapping",
        lambda _viewer_table, mapping_id: fake_codes.get(mapping_id, []),
    )

    plan = mdi.build_merge_plan_for_identity(
        identity="urn1",
        group=users,
        viewer_table=object(),
        lpa_table=object(),
    )

    assert plan["primary_user_id"] == "primary"
    assert plan["canonical_mapping_ids"] == ["primary-map"]
    assert plan["move_mapping_ids"] == []
    assert plan["delete_mapping_ids"] == ["secondary-map"]
    assert plan["viewer_code_updates"] == [
        {
            "viewer_code": "VC2",
            "from_mapping_id": "secondary-map",
            "to_mapping_id": "primary-map",
        }
    ]
    assert plan["delete_secondary_user_ids"] == ["secondary"]


def test_build_merge_plan_empty_secondary_account(monkeypatch):
    users = [
        make_user("primary", "urn1", "2026-03-10T08:49:11.883265"),
        make_user("secondary", "urn1", "2026-03-10T08:49:11.883262"),
    ]

    mappings_by_user = {
        "primary": [
            make_mapping("m1", "primary", 9, sirius_uid="700000000047"),
        ],
        "secondary": [],
    }

    monkeypatch.setattr(
        mdi,
        "get_user_lpas",
        lambda _lpa_table, user_id: mappings_by_user[user_id],
    )
    monkeypatch.setattr(
        mdi,
        "get_viewer_codes_for_mapping",
        lambda _viewer_table, _mapping_id: [],
    )

    plan = mdi.build_merge_plan_for_identity(
        identity="urn1",
        group=users,
        viewer_table=object(),
        lpa_table=object(),
    )

    assert plan["move_mapping_ids"] == []
    assert plan["delete_mapping_ids"] == []
    assert plan["viewer_code_updates"] == []
    assert plan["delete_secondary_user_ids"] == ["secondary"]


def test_save_merge_plan_writes_json(tmp_path, monkeypatch):
    plan = [
        {
            "identity": "urn1",
            "primary_user_id": "primary",
            "secondary_user_ids": ["secondary"],
            "all_mappings": [],
            "canonical_mapping_ids": ["m1"],
            "move_mapping_ids": [],
            "delete_mapping_ids": [],
            "viewer_code_updates": [],
            "mapping_viewer_codes": {},
            "delete_secondary_user_ids": ["secondary"],
        }
    ]

    monkeypatch.chdir(tmp_path)

    mdi.save_merge_plan("demo", plan)

    files = list(tmp_path.glob("merge_plan_demo_*.json"))
    assert len(files) == 1

    data = json.loads(files[0].read_text())
    assert data[0]["identity"] == "urn1"
    assert data[0]["primary_user_id"] == "primary"
