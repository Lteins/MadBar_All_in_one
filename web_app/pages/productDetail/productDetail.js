import util from "../../lib/utility";
import Product from "../../models/Product";
const app = getApp();

Page({
  data: {
    array: app.globalData.metaProductTypes,
    index: 0
  },
})