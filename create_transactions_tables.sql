-- Create Transactions and TransactionItems tables
-- Run this script in SQL Server Management Studio if the tables don't exist

-- Create Transactions table
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[Transactions]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[Transactions](
        [TransactionID] [int] IDENTITY(1,1) NOT NULL,
        [Customer_ID] [int] NOT NULL,
        [Employee_ID] [int] NOT NULL,
        [Transaction_Type] [nvarchar](50) NULL,
        [Total_No_Of_Items] [int] NOT NULL DEFAULT 0,
        [Transaction_Date] [datetime] NULL DEFAULT GETDATE(),
        PRIMARY KEY CLUSTERED ([TransactionID] ASC)
    )
    
    -- Add foreign key constraints
    ALTER TABLE [dbo].[Transactions] ADD CONSTRAINT [FK_Transactions_Customer] 
        FOREIGN KEY([Customer_ID]) REFERENCES [dbo].[Customer] ([CustomerID])
    
    ALTER TABLE [dbo].[Transactions] ADD CONSTRAINT [FK_Transactions_Employee] 
        FOREIGN KEY([Employee_ID]) REFERENCES [dbo].[Management] ([ManagementID])
END
GO

-- Create TransactionItems table
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[TransactionItems]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[TransactionItems](
        [TransactionItemID] [int] IDENTITY(1,1) NOT NULL,
        [Transaction_ID] [int] NOT NULL,
        [Item_ID] [int] NOT NULL,
        [Quantity] [int] NOT NULL,
        [PriceAtTime] [decimal](10, 2) NOT NULL,
        PRIMARY KEY CLUSTERED ([TransactionItemID] ASC)
    )
    
    -- Add foreign key constraints
    ALTER TABLE [dbo].[TransactionItems] ADD CONSTRAINT [FK_TransactionItems_Transaction] 
        FOREIGN KEY([Transaction_ID]) REFERENCES [dbo].[Transactions] ([TransactionID])
    
    ALTER TABLE [dbo].[TransactionItems] ADD CONSTRAINT [FK_TransactionItems_Inventory] 
        FOREIGN KEY([Item_ID]) REFERENCES [dbo].[Inventory] ([ItemID])
END
GO
