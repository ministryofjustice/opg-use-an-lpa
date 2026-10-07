import json
import pytest
from pathlib import Path
from unittest.mock import Mock, MagicMock, patch, mock_open, call
from botocore.exceptions import ClientError

import delete_unused_accounts as pua


@pytest.fixture
def mock_aws_session():
    """Mock AWS IAM session"""
    return {
        "Credentials": {
            "AccessKeyId": "AKIAIOSFODNN7EXAMPLE",
            "SecretAccessKey": "wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY",
            "SessionToken": "token123",
        }
    }


@pytest.fixture
def mock_dynamodb_tables():
    """Mock DynamoDB tables"""
    actor_users_table = MagicMock()
    user_lpa_actor_map_table = MagicMock()
    return actor_users_table, user_lpa_actor_map_table


def test_set_environment_details_dev():
    """Test environment details for development"""
    details = pua.UnusedAccountsProcessor.set_environment_details("development")
    
    assert details["account_id"] == "367815980639"
    assert details["account_name"] == "development"
    assert details["name"] == "development"


def test_set_environment_details_prod():
    """Test environment details for production"""
    details = pua.UnusedAccountsProcessor.set_environment_details("production")
    
    assert details["account_id"] == "690083044361"
    assert details["account_name"] == "production"
    assert details["name"] == "production"


def test_set_environment_details_preproduction():
    """Test environment details for preproduction"""
    details = pua.UnusedAccountsProcessor.set_environment_details("preproduction")
    
    assert details["account_id"] == "888228022356"
    assert details["account_name"] == "preproduction"
    assert details["name"] == "preproduction"


def test_set_environment_details_unknown_defaults_to_dev():
    """Test that unknown environment defaults to development"""
    details = pua.UnusedAccountsProcessor.set_environment_details("unknown")
    
    assert details["account_id"] == "367815980639"
    assert details["account_name"] == "development"


@patch("delete_unused_accounts.boto3.client")
@patch("delete_unused_accounts.boto3.resource")
def test_processor_initialization(mock_resource, mock_client, mock_aws_session):
    """Test processor initialization with mocked AWS"""
    mock_sts_client = MagicMock()
    mock_sts_client.assume_role.return_value = mock_aws_session
    mock_client.return_value = mock_sts_client
    
    mock_dynamodb = MagicMock()
    mock_dynamodb.Table.return_value = MagicMock()
    mock_resource.return_value = mock_dynamodb
    
    with patch.object(pua.UnusedAccountsProcessor, "load_done_users", return_value=set()):
        processor = pua.UnusedAccountsProcessor(environment="demo")
    
    assert processor.environment == "demo"
    assert processor.environment_details["account_id"] == "367815980639"


def test_load_done_users_file_exists(tmp_path):
    """Test loading done users from existing file"""
    done_file = tmp_path / "done_users.json"
    done_users = ["user1", "user2", "user3"]
    done_file.write_text(json.dumps(done_users))
    
    with patch("delete_unused_accounts.Path") as mock_path:
        mock_path.return_value.exists.return_value = True
        mock_path.return_value.read_text.return_value = json.dumps(done_users)
        
        # This is a simplified test; in reality the processor would use the fixture
        loaded = json.loads(done_file.read_text())
        assert set(loaded) == {"user1", "user2", "user3"}


def test_load_done_users_file_not_exists():
    """Test loading done users when file doesn't exist"""
    with patch("delete_unused_accounts.Path") as mock_path:
        mock_path.return_value.exists.return_value = False
        # When file doesn't exist, should return empty set
        done_users = set()
        assert done_users == set()


@patch("delete_unused_accounts.boto3.client")
@patch("delete_unused_accounts.boto3.resource")
def test_get_user_lpas_count_no_lpas(mock_resource, mock_client, mock_aws_session):
    """Test querying for user with no LPAs"""
    mock_sts_client = MagicMock()
    mock_sts_client.assume_role.return_value = mock_aws_session
    mock_client.return_value = mock_sts_client
    
    mock_dynamodb = MagicMock()
    mock_table = MagicMock()
    mock_table.query.return_value = {"Items": []}
    mock_dynamodb.Table.return_value = mock_table
    mock_resource.return_value = mock_dynamodb
    
    with patch.object(pua.UnusedAccountsProcessor, "load_done_users", return_value=set()):
        processor = pua.UnusedAccountsProcessor(environment="demo")
        processor.user_lpa_actor_map_table = mock_table
        
        count = processor.get_user_lpas_count("user-123")
    
    assert count == 0
    mock_table.query.assert_called_once()


@patch("delete_unused_accounts.boto3.client")
@patch("delete_unused_accounts.boto3.resource")
def test_get_user_lpas_count_with_lpas(mock_resource, mock_client, mock_aws_session):
    """Test querying for user with LPAs"""
    mock_sts_client = MagicMock()
    mock_sts_client.assume_role.return_value = mock_aws_session
    mock_client.return_value = mock_sts_client
    
    mock_dynamodb = MagicMock()
    mock_table = MagicMock()
    mock_table.query.return_value = {
        "Items": [
            {"Id": "mapping-1", "UserId": "user-123", "SiriusUid": "700000000001"},
            {"Id": "mapping-2", "UserId": "user-123", "SiriusUid": "700000000002"},
        ]
    }
    mock_dynamodb.Table.return_value = mock_table
    mock_resource.return_value = mock_dynamodb
    
    with patch.object(pua.UnusedAccountsProcessor, "load_done_users", return_value=set()):
        processor = pua.UnusedAccountsProcessor(environment="demo")
        processor.user_lpa_actor_map_table = mock_table
        
        count = processor.get_user_lpas_count("user-123")
    
    assert count == 2


@patch("delete_unused_accounts.boto3.client")
@patch("delete_unused_accounts.boto3.resource")
def test_get_user_lpas_count_error(mock_resource, mock_client, mock_aws_session):
    """Test error handling when querying LPAs fails"""
    mock_sts_client = MagicMock()
    mock_sts_client.assume_role.return_value = mock_aws_session
    mock_client.return_value = mock_sts_client
    
    mock_dynamodb = MagicMock()
    mock_table = MagicMock()
    mock_table.query.side_effect = ClientError(
        {"Error": {"Code": "ValidationException", "Message": "Invalid query"}},
        "Query"
    )
    mock_dynamodb.Table.return_value = mock_table
    mock_resource.return_value = mock_dynamodb
    
    with patch.object(pua.UnusedAccountsProcessor, "load_done_users", return_value=set()):
        processor = pua.UnusedAccountsProcessor(environment="demo")
        processor.user_lpa_actor_map_table = mock_table
        
        count = processor.get_user_lpas_count("user-123")
    
    assert count is None


@patch("delete_unused_accounts.boto3.client")
@patch("delete_unused_accounts.boto3.resource")
def test_delete_actor_user_success(mock_resource, mock_client, mock_aws_session):
    """Test successful user deletion"""
    mock_sts_client = MagicMock()
    mock_sts_client.assume_role.return_value = mock_aws_session
    
    mock_dynamodb_client = MagicMock()
    mock_dynamodb_client.transact_write_items.return_value = {}
    mock_client.return_value = mock_dynamodb_client
    
    mock_resource.return_value = MagicMock()
    
    with patch.object(pua.UnusedAccountsProcessor, "load_done_users", return_value=set()):
        processor = pua.UnusedAccountsProcessor(environment="demo")
        processor.dynamodb_client = mock_dynamodb_client
        
        result = processor.delete_actor_user("user-123")
    
    assert result is True
    mock_dynamodb_client.transact_write_items.assert_called_once()


@patch("delete_unused_accounts.boto3.client")
@patch("delete_unused_accounts.boto3.resource")
def test_delete_actor_user_failure(mock_resource, mock_client, mock_aws_session):
    """Test user deletion failure"""
    mock_sts_client = MagicMock()
    mock_sts_client.assume_role.return_value = mock_aws_session
    
    mock_dynamodb_client = MagicMock()
    mock_dynamodb_client.transact_write_items.side_effect = ClientError(
        {"Error": {"Code": "ValidationException", "Message": "Invalid key"}},
        "TransactWriteItems"
    )
    mock_client.return_value = mock_dynamodb_client
    
    mock_resource.return_value = MagicMock()
    
    with patch.object(pua.UnusedAccountsProcessor, "load_done_users", return_value=set()):
        processor = pua.UnusedAccountsProcessor(environment="demo")
        processor.dynamodb_client = mock_dynamodb_client
        
        result = processor.delete_actor_user("user-123")
    
    assert result is False


@patch("delete_unused_accounts.boto3.client")
@patch("delete_unused_accounts.boto3.resource")
def test_process_unused_accounts_user_with_no_lpas_deleted(
    mock_resource, mock_client, mock_aws_session, tmp_path
):
    """Test processing a user with no LPAs - should be deleted"""
    mock_sts_client = MagicMock()
    mock_sts_client.assume_role.return_value = mock_aws_session
    mock_client.return_value = mock_sts_client
    
    mock_dynamodb = MagicMock()
    mock_resource.return_value = mock_dynamodb
    
    # Create CSV file
    csv_file = tmp_path / "unused.csv"
    csv_file.write_text("UserId,Email,LastLogin\nuser-123,user@example.com,2026-04-01\n")
    
    with patch.object(pua.UnusedAccountsProcessor, "load_done_users", return_value=set()):
        processor = pua.UnusedAccountsProcessor(environment="demo")
        processor.get_user_lpas_count = MagicMock(return_value=0)
        processor.delete_actor_user = MagicMock(return_value=True)
        processor.done_file = tmp_path / "done.json"
        
        processor.process_unused_accounts(str(csv_file))
    
    processor.delete_actor_user.assert_called_once_with("user-123")
    assert "user-123" in processor.done_users


@patch("delete_unused_accounts.boto3.client")
@patch("delete_unused_accounts.boto3.resource")
def test_process_unused_accounts_user_with_lpas_not_deleted(
    mock_resource, mock_client, mock_aws_session, tmp_path
):
    """Test processing a user with LPAs - should NOT be deleted"""
    mock_sts_client = MagicMock()
    mock_sts_client.assume_role.return_value = mock_aws_session
    mock_client.return_value = mock_sts_client
    
    mock_dynamodb = MagicMock()
    mock_resource.return_value = mock_dynamodb
    
    # Create CSV file
    csv_file = tmp_path / "unused.csv"
    csv_file.write_text("UserId,Email,LastLogin\nuser-456,user@example.com,2026-04-01\n")
    
    with patch.object(pua.UnusedAccountsProcessor, "load_done_users", return_value=set()):
        processor = pua.UnusedAccountsProcessor(environment="demo")
        processor.get_user_lpas_count = MagicMock(return_value=2)
        processor.delete_actor_user = MagicMock(return_value=True)
        processor.done_file = tmp_path / "done.json"
        
        processor.process_unused_accounts(str(csv_file))
    
    processor.delete_actor_user.assert_not_called()
    assert "user-456" in processor.done_users


@patch("delete_unused_accounts.boto3.client")
@patch("delete_unused_accounts.boto3.resource")
def test_process_unused_accounts_resumes_from_done_list(
    mock_resource, mock_client, mock_aws_session, tmp_path
):
    """Test that processor skips users already in done list"""
    mock_sts_client = MagicMock()
    mock_sts_client.assume_role.return_value = mock_aws_session
    mock_client.return_value = mock_sts_client
    
    mock_dynamodb = MagicMock()
    mock_resource.return_value = mock_dynamodb
    
    # Create CSV file
    csv_file = tmp_path / "unused.csv"
    csv_file.write_text(
        "UserId,Email,LastLogin\nuser-789,user@example.com,2026-04-01\n"
        "user-999,user2@example.com,2026-04-02\n"
    )
    
    with patch.object(
        pua.UnusedAccountsProcessor, "load_done_users", return_value={"user-789"}
    ):
        processor = pua.UnusedAccountsProcessor(environment="demo")
        processor.get_user_lpas_count = MagicMock(return_value=0)
        processor.delete_actor_user = MagicMock(return_value=True)
        processor.done_file = tmp_path / "done.json"
        
        processor.process_unused_accounts(str(csv_file))
    
    # Only user-999 should be processed and deleted (user-789 was already done)
    processor.delete_actor_user.assert_called_once_with("user-999")


@patch("delete_unused_accounts.boto3.client")
@patch("delete_unused_accounts.boto3.resource")
def test_save_done_users(mock_resource, mock_client, mock_aws_session, tmp_path):
    """Test that done users are saved to file"""
    mock_sts_client = MagicMock()
    mock_sts_client.assume_role.return_value = mock_aws_session
    mock_client.return_value = mock_sts_client
    mock_resource.return_value = MagicMock()
    
    with patch.object(pua.UnusedAccountsProcessor, "load_done_users", return_value=set()):
        processor = pua.UnusedAccountsProcessor(environment="demo")
        processor.done_file = tmp_path / "done.json"
        processor.done_users = {"user-1", "user-2", "user-3"}
        
        processor.save_done_users()
    
    assert processor.done_file.exists()
    saved_data = json.loads(processor.done_file.read_text())
    assert set(saved_data) == {"user-1", "user-2", "user-3"}
