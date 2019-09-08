<?php

    # convenient class for accessing different products
    # @author: Gaochang Li
date_default_timezone_set('UTC');
    final class ProductsExplorer
    {

        private function __construct()
        {
        }

        /**
         * Get all products sold by a user referred by a user ID.
         * @TODO: We should log these errors, currently there's no way of knowing
         *       that bad thing has happended.
         * @param int $UserId UserId.
         * @param const $Status ProductStatus constant of which the products
         *       to return should have (OPTIONAL, defaults to only active products).
         *       Pass in NULL to get products of all status.
         * @param $StartId parameter used for pagination (OPTIONAL, defaults to the most recent)
         * @param $ListLength parameter used for pagination (OPTIONAL, defaults to everything)
         * @return An array of product ID.
         */
        public static function GetProductsByUserId($UserId, $Status = Product::PSTATUS_ACTIVE,
                $StartId = -1, $ListLength = -1)
        {
            $UserId = intval($UserId);
            // echo $Status;
            $DB = new Database();

            # check if user Id is valid
            $DB->Query("SELECT * FROM Users WHERE UserId = ".$UserId);
            if ($DB->NumOfRows() < 1) {
                return array();
            }

            # if want all products or status code invalid
            if ($Status != Product::PSTATUS_ACTIVE &&
                $Status != Product::PSTATUS_EXPIRED &&
                $Status != Product::PSTATUS_ENDED){
                $SQL = "SELECT ProductId From Products WHERE ProductOwner = " . $UserId;
            }
            else {
                $Code = Product::GetStatusCodeForDB($Status);
                $SQL = "SELECT ProductId From Products WHERE ProductOwner = "
                        . $UserId . " AND ProductStatus = " . $Code;
            }
            # main work happens here
            $DB->Query(self::Pagination($SQL, $StartId, $ListLength));
            $Ret = array();
            while ($Row = $DB->NextRow()) {
                $Ret[] = $Row["ProductId"];
            }
            $DB->Close();
            return $Ret;
        }

        /**
         * Get the products with a certain product type defined in Product class.
         * @param const $Type Product type constant.
         * @param $StartId parameter used for pagination (OPTIONAL, defaults to the most recent)
         * @param $ListLength parameter used for pagination (OPTIONAL, defaults to everything)
         * @return An array of product IDs of all the products of the required type.
         */
        public static function GetProductsByType($Type, $StartId = -1, $ListLength = -1)
        {
            $Code = Product::GetTypeCodeForDB($Type);
            $Ret = array();
            $DB = new Database();

            $StaCode = Product::GetStatusCodeForDB(Product::PSTATUS_ACTIVE);

            $SQL = self::Pagination("SELECT ProductId FROM Products WHERE ProductType = " . $Code .
                " AND ProductStatus = " . $StaCode, $StartId, $ListLength);

            $DB->Query($SQL);
            while ($Row = $DB->NextRow()) {
                $Ret[] = $Row["ProductId"];
            }
            $DB->Close();
            return $Ret;
        }

        /**
         * Get all active products ordered by productId.
         * @param $StartId parameter used for pagination (OPTIONAL, defaults to the most recent)
         * @param $ListLength parameter used for pagination (OPTIONAL, defaults to everything)
         * @return An array of ALL product ID that are ACTIVE.
         */
        public static function GetAllActiveProducts($StartId = -1, $ListLength = -1)
        {
            $Code = Product::GetStatusCodeForDB(Product::PSTATUS_ACTIVE);
            $Ret = array();
            $DB = new Database();
            $DB->Query(self::Pagination("SELECT ProductId FROM Products WHERE ProductStatus = "
                . $Code, $StartId, $ListLength));

            while ($Row = $DB->NextRow()) {
                $Ret[] = $Row["ProductId"];
            }
            $DB->Close();
            return $Ret;
        }

        public static function SearchProducts($Pat, $Offset = 0, $Type = -1, $ListLength = 20){
            $DB = new Database();
            $Code = Product::GetStatusCodeForDB(Product::PSTATUS_ACTIVE);
            $TempQuery = "SELECT ProductId, MATCH (ProductName,ProductDescription) AGAINST ('$Pat' IN NATURAL LANGUAGE MODE) AS score FROM Products WHERE MATCH (ProductName,ProductDescription) AGAINST ('$Pat' IN NATURAL LANGUAGE MODE)>0 AND ProductStatus = $Code" ;
            if ($Type != -1)  $TempQuery .= " AND ProductType = $Type";
            $TempQuery .= " ORDER BY score DESC LIMIT $Offset, $ListLength";
            $DB->Query($TempQuery);

            $Ret = array();
            while ($Row = $DB->NextRow()) {
                $Ret[] = $Row["ProductId"];
            }
            $DB->Close();
            return $Ret;
        }

        /**
         * This method modifies the given query for the purpose of pagination. It takes in the original query,
         * change the query list to descending order by product ID, and takes only a specified amount of products
         * with ID smaller than (i.e. posted earlier than) a specified product.
         *
         * @note: If $StartId is out of bound, mysql will ignore this condition.
         * @param string $Query the original query by default order.
         * @param int $StartId the ID of the specified product before the newest product to be returned
         * @param int $ListLength the size of the sublist to be returned
         * @return a new SQL query modified for purpose of pagination.
         */
        private static function Pagination($Query, $StartId, $ListLength)
        {
            $TempQuery = "SELECT * FROM (" . $Query . " ORDER BY ProductId DESC) AS temp";
            if ($StartId > 0)
                $TempQuery = $TempQuery . " WHERE temp.ProductID < " . $StartId;
            if ($ListLength > 0)
                $TempQuery = $TempQuery . " LIMIT " . $ListLength;
            return $TempQuery;
        }
    }
?>
