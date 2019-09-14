import util from "../../lib/utility";
import Product from "../../models/Product";
const app = getApp();

Page({
  data: {
    array: util.getProductTypeArray(),
    index: 0,
    contactURL: "",
    buttonMsg: "查看卖家二维码",
    toView: "productGallery"
  },
  onPullDownRefresh: function () {
    wx.stopPullDownRefresh();
  },
  onLoad: function (options) {
    wx.showShareMenu({
      withShareTicket: true
    })
    // id is required
    if (!options["id"]) {
      // console.error("productDetail页面需要一个id作为argument");
      return;
    }

    // product info could already be present
    if (false && app.globalData["dispayedProduct"]
      && app.globalData["dispayedProduct"]["productId"] == options["id"]) {

      util.getContactById(app.globalData.dispayedProduct.productOwner)
        .then(contactURL => {
          this.setData({
            contactURL: contactURL
          });
        });

      this.setData({
        'imgs': app.globalData["dispayedProduct"]["productImages"],
        'description': app.globalData["dispayedProduct"]["productDescription"],
        'type': app.globalData["dispayedProduct"]["productType"]
      });

    }

    // otherwise we need to load it
    else {
      wx.showLoading({
        title: '正在加载...',
      });
      const timer = setTimeout(() => {
        // a fail safe mechnism - we dont want to show it forever...
        wx.hideLoading();
      }, 5000);


      util.getAllActiveProducts().then
        (products => {
          // console.log(products);
          for (var id in products) {
            var product = products[id];
            if (product['productId'] == options['id']) {

              this.setData({
                'imgs': product["productImages"],
                'description': product["productDescription"],
                'type': product["productType"]
              });

              return util.getContactById(product.productOwner)
                .then(contactURL => {
                  this.setData({
                    contactURL: contactURL
                  });
                });
            }
          }
        })
        .then(wx.hideLoading)
    }
  },
  onShareAppMessage(config) {
    const images = app.globalData["dispayedProduct"].productImages;
    const productId = app.globalData["dispayedProduct"].productId;
    return {
      title: app.globalData["dispayedProduct"].productName,
      desc: app.globalData["dispayedProduct"].productDescription,
      path: 'pages/productDetail/productDetail?id=' + productId,
      imageUrl: images.length > 0 ? images[0] : '/images/noPicture.png',
      success: function (res) {
        util.showToast(0, '分享成功');
      },
      fail: function (res) {
        util.showToast(0, '分享失败');
      }
    }
  },
  imgErr: function (event) {
    let buffer = {};
    buffer[event.currentTarget.id] = "/images/noPicture.png";
    this.setData(buffer);
  },
  previewContact: function () {
    // DEPRECATED
    util.getContactById(app.globalData.dispayedProduct.productOwner)
      .then(contactURL => {
        // console.log("ContactURL: " + contactURL);
        return contactURL;
      })
      .then(contactURL => {
        wx.previewImage({
          urls: [contactURL], success: (feedback => {
            // console.log("Successfully Preview");
            // console.log(feedback);
          })
        })
      });
  },
  previewContactImage: function (e) {
    wx.showModal({
      title: "说明",
      content: '请发送到聊天窗口再扫码',
      success: function (res) {
        if (res.confirm) {
          var current = e.target.dataset.src;
          wx.previewImage({
            current: current,
            urls: [current]
          })
        } else {
        }
      }
    })
  },
  tap: function (e) {
    if (this.data["buttonMsg"] === "查看卖家二维码") {
      this.setData({
        toView: "contactImage",
        buttonMsg: "返回"
      })
    } else {
      this.setData({
        toView: "productGallery",
        buttonMsg: "查看卖家二维码"
      })
    }
  },
  previewImages: function () {
    wx.previewImage({ urls: this.data.imgs });
  },
  goHome: function () {
    wx.switchTab({
      url: '/pages/index/index',
    });
  },
})